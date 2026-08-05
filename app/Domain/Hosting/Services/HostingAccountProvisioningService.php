<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostedApp;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HostingBackup;
use App\Domain\Hosting\Models\HostingDatabase;
use App\Domain\Hosting\Support\SubdirectoryGenerator;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orquestra a sequência de Actions no Agent para provisionar (ou
 * desprovisionar) uma conta de hospedagem. Cada chamada ao Agent é
 * síncrona e registrada como um AgentJob (auditoria). Se um passo
 * falhar no meio da sequência, desfaz best-effort o que já foi criado
 * antes de marcar a conta como erro — não é uma transação atômica de
 * verdade (não existe isso entre dois processos), é compensação.
 */
class HostingAccountProvisioningService
{
    /**
     * Chave salva em php_fpm_settings.error_reporting => expressão PHP
     * real escrita no pool (ver config('hosting.error_reporting_presets')
     * pros rótulos mostrados no formulário).
     */
    private const ERROR_REPORTING_PRESETS = [
        'production' => 'E_ALL & ~E_DEPRECATED & ~E_NOTICE',
        'all' => 'E_ALL',
        'none' => '0',
    ];

    public function __construct(
        private AgentHttpClient $client,
        private FolderProtectionService $folderProtections,
        private SiteRedirectService $siteRedirects,
        private HotlinkProtectionService $hotlinkProtection,
        private MimeTypeService $mimeTypes,
        private SshAccessService $ssh,
    ) {
    }

    public function provision(HostingAccount $account): void
    {
        $server = $account->server;
        $username = $account->linux_username;
        $domain = $account->primary_domain;
        $phpVersion = $account->php_version;

        $completed = [];

        try {
            $this->runStep($server, 'linux.create_user', ['username' => $username]);
            $completed[] = 'user';

            // Senha de SSH gerada já na criação — desde a mudança pra
            // login por usuário da hospedagem, essa é a MESMA credencial
            // usada pra entrar no painel, então precisa existir desde já
            // (não só quando o SSH é ligado manualmente pela primeira
            // vez). ssh_enabled continua false por padrão — só a senha
            // passa a existir, o acesso por shell continua bloqueado no
            // servidor até ser liberado explicitamente. Falha aqui aborta
            // a provisão inteira (não é best-effort): sem essa senha o
            // cliente não consegue nem entrar no painel.
            $this->ssh->setPassword($account, Str::password(20));

            $this->runStep($server, 'php.create_pool', ['username' => $username, 'php_version' => $phpVersion]);
            $completed[] = 'pool';

            $this->runStep($server, 'web.create_vhost', ['username' => $username, 'domain' => $domain, 'php_version' => $phpVersion, 'public_path' => $account->public_path]);
            $completed[] = 'vhost';

            $account->update(['status' => 'active', 'last_provision_error' => null]);

            // Best-effort: se o DNS ainda não estiver apontado, isso só
            // fica "failed" — não desfaz a conta por causa disso, o
            // admin/cliente pode tentar de novo manualmente depois.
            $this->issueSslCertificate($account);

            // Best-effort também — cgroup/quota faltando não deve travar
            // a conta de ficar ativa, o admin pode reaplicar depois pela
            // tela de Recursos. Só loga, não tem campo de status próprio
            // (diferente do SSL) porque é síncrono e já teria retornado
            // erro claro se algo realmente tivesse quebrado.
            try {
                $this->syncResourceLimits($account);
            } catch (Throwable $e) {
                logger()->warning('Falha ao aplicar limites de recursos na provisão', [
                    'hosting_account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (Throwable $e) {
            $this->rollback($server, $completed, $username, $domain);

            $account->update(['status' => 'error', 'last_provision_error' => $e->getMessage()]);
        }
    }

    /**
     * Remove tudo que existir no Agent para esta conta (usada tanto na
     * exclusão real quanto no rollback de uma provisão que falhou).
     */
    public function deprovision(HostingAccount $account): void
    {
        foreach ($account->databases as $database) {
            $this->deleteDatabase($database);
        }

        foreach ($account->domains as $domain) {
            $this->removeDomain($domain);
        }

        $this->rollback($account->server, ['vhost', 'pool', 'user'], $account->linux_username, $account->primary_domain);
    }

    /**
     * Domínio adicional/subdomínio — reaproveita usuário Linux e pool
     * PHP-FPM da conta, só ganha subdiretório e vhost próprios.
     */
    public function addDomain(HostingAccount $account, string $domainName, string $type, string $location = 'inside_public_html'): Domain
    {
        if ($account->domains()->count() >= $account->plan->max_addon_domains) {
            throw new RuntimeException('Limite de domínios adicionais do plano atingido.');
        }

        $outside = $location === 'outside_public_html';
        $subdir = $outside ? null : SubdirectoryGenerator::fromDomain($account, $domainName);

        $domain = $account->domains()->create([
            'domain' => $domainName,
            'type' => $type,
            'location' => $location,
            'subdirectory' => $subdir,
            'status' => 'creating',
        ]);

        try {
            $this->runStep($account->server, 'web.create_addon_domain', [
                'username' => $account->linux_username,
                'domain' => $domainName,
                'location' => $outside ? 'outside' : 'inside',
                'subdir' => $subdir,
                'php_version' => $account->php_version,
            ]);

            $domain->update(['status' => 'active']);

            $this->issueSslCertificate($account, $domain);
        } catch (Throwable $e) {
            $domain->update(['status' => 'error', 'last_error' => $e->getMessage()]);
        }

        return $domain->fresh();
    }

    public function removeDomain(Domain $domain): void
    {
        $account = $domain->hostingAccount;

        $this->client->dispatch($account->server, 'web.delete_addon_domain', [
            'username' => $account->linux_username,
            'domain' => $domain->domain,
            'location' => $domain->isOutsidePublicHtml() ? 'outside' : 'inside',
            'subdir' => $domain->subdirectory,
        ]);

        $domain->delete();
    }

    /**
     * Uma conta pode ter vários bancos (estilo cPanel/DirectAdmin). Nome
     * do banco e do usuário vêm prefixados com o username Linux — que
     * já é único globalmente — pra nunca colidir entre contas
     * diferentes; só precisam ser únicos dentro da própria conta.
     * Usuário e senha são escolhidos por quem cria (admin/cliente); se
     * não informar usuário, reaproveita o nome do banco; se não
     * informar senha, gera uma aleatória.
     */
    public function provisionDatabase(HostingAccount $account, string $dbSuffix, ?string $userSuffix = null, ?string $password = null): HostingDatabase
    {
        if ($account->databases()->count() >= $account->plan->max_databases) {
            throw new RuntimeException('Limite de bancos de dados do plano atingido.');
        }

        $dbName = "{$account->linux_username}_{$dbSuffix}";
        $dbUsername = "{$account->linux_username}_".($userSuffix ?: $dbSuffix);

        if (HostingDatabase::where('db_name', $dbName)->exists()) {
            throw new RuntimeException('Já existe um banco com esse nome nessa conta.');
        }

        if (HostingDatabase::where('db_username', $dbUsername)->exists()) {
            throw new RuntimeException('Já existe um usuário com esse nome nessa conta.');
        }

        $dbPassword = $password ?: Str::password(24);

        $this->runStep($account->server, 'database.create_mysql', [
            'db_name' => $dbName,
            'db_username' => $dbUsername,
            'db_password' => $dbPassword,
        ]);

        return $account->databases()->create([
            'db_name' => $dbName,
            'db_username' => $dbUsername,
            'db_password' => $dbPassword,
        ]);
    }

    public function deleteDatabase(HostingDatabase $database): void
    {
        $account = $database->hostingAccount;

        $this->client->dispatch($account->server, 'database.delete_mysql', [
            'db_name' => $database->db_name,
            'db_username' => $database->db_username,
        ]);

        $database->delete();
    }

    /**
     * Assíncrona — só dispara e marca "pending". O resultado final
     * (ativo/falhou) chega depois via callback do Agent, tratado em
     * AgentWebhookController::callback(). Passar $domain emite para um
     * domínio adicional/subdomínio; sem ele, emite para o domínio
     * principal da conta.
     */
    public function issueSslCertificate(HostingAccount $account, ?Domain $domain = null): void
    {
        $this->client->dispatch($account->server, 'ssl.issue_certificate', [
            'username' => $account->linux_username,
            'domain' => $domain ? $domain->domain : $account->primary_domain,
            'location' => $domain?->isOutsidePublicHtml() ? 'outside' : 'inside',
            'subdir' => $domain?->subdirectory,
            'php_version' => $account->php_version,
            'public_path' => $domain ? $domain->public_path : $account->public_path,
        ]);

        if ($domain) {
            $domain->update(['ssl_status' => 'pending', 'ssl_error' => null]);
        } else {
            $account->update(['ssl_status' => 'pending', 'ssl_error' => null]);
        }
    }

    /**
     * Muda a subpasta que o nginx serve de fato (ex.: "public", pra um
     * projeto Laravel/Symfony extraído com a árvore inteira do
     * framework) — reescreve só o vhost, não mexe em arquivo nenhum.
     */
    public function updateDocumentRoot(HostingAccount $account, ?string $publicPath): void
    {
        $this->runStep($account->server, 'web.update_document_root', [
            'username' => $account->linux_username,
            'domain' => $account->primary_domain,
            'php_version' => $account->php_version,
            'ssl_active' => $account->ssl_status === 'active',
            'public_path' => $publicPath,
        ]);

        $account->update(['public_path' => $publicPath]);
    }

    /**
     * Mesma ideia do método acima, pra um domínio adicional/subdomínio.
     */
    public function updateDomainDocumentRoot(Domain $domain, ?string $publicPath): void
    {
        $account = $domain->hostingAccount;

        $this->runStep($account->server, 'web.update_document_root', [
            'username' => $account->linux_username,
            'domain' => $domain->domain,
            'location' => $domain->isOutsidePublicHtml() ? 'outside' : 'inside',
            'subdir' => $domain->subdirectory,
            'php_version' => $account->php_version,
            'ssl_active' => $domain->ssl_status === 'active',
            'public_path' => $publicPath,
        ]);

        $domain->update(['public_path' => $publicPath]);
    }

    /**
     * Troca a versão de PHP de uma conta já provisionada: cria o pool
     * na versão nova, apaga o da antiga (SwitchPhpVersionAction, uma
     * chamada só), depois reescreve o vhost de cada domínio da conta
     * (principal + adicionais) pra apontar pro socket novo, preservando
     * SSL onde já tiver certificado ativo.
     */
    public function changePhpVersion(HostingAccount $account, string $newVersion): void
    {
        $oldVersion = $account->php_version;

        if ($oldVersion === $newVersion) {
            return;
        }

        $server = $account->server;
        $username = $account->linux_username;

        // Repassa os pool settings customizados da conta (se tiver
        // algum salvo) — sem isso, a troca de versão silenciosamente
        // resetaria memory_limit/upload_max_filesize/etc pro padrão
        // global (ver PhpFpmPoolSettings::variables no Agent).
        $this->runStep($server, 'php.switch_version', array_merge([
            'username' => $username,
            'old_php_version' => $oldVersion,
            'new_php_version' => $newVersion,
        ], $account->php_fpm_settings ? $this->formatPoolSettings($account->php_fpm_settings) : []));

        // Domínios em modo app (Node.js/Python) não usam PHP-FPM — o
        // vhost deles é proxy_pass, não faz sentido regenerar pro socket
        // da nova versão nem resincronizar proteção/redirect/hotlink
        // (a pasta virou o alvo do proxy, não conteúdo servido por
        // localização).
        $appDomains = HostedApp::where('hosting_account_id', $account->id)->pluck('domain')->all();

        if (! in_array($account->primary_domain, $appDomains, true)) {
            $this->runStep($server, 'web.update_vhost_php_version', [
                'username' => $username,
                'domain' => $account->primary_domain,
                'php_version' => $newVersion,
                'ssl_active' => $account->ssl_status === 'active',
                'public_path' => $account->public_path,
            ]);
            $this->folderProtections->resyncIfNeeded($account, $account->primary_domain);
            $this->siteRedirects->resyncIfNeeded($account, $account->primary_domain);
            $this->hotlinkProtection->resyncIfNeeded($account, $account->primary_domain);
            $this->mimeTypes->resyncIfNeeded($account, $account->primary_domain);
        }

        foreach ($account->domains as $domain) {
            if (in_array($domain->domain, $appDomains, true)) {
                continue;
            }

            $this->runStep($server, 'web.update_vhost_php_version', [
                'username' => $username,
                'domain' => $domain->domain,
                'location' => $domain->isOutsidePublicHtml() ? 'outside' : 'inside',
                'subdir' => $domain->subdirectory,
                'php_version' => $newVersion,
                'ssl_active' => $domain->ssl_status === 'active',
                'public_path' => $domain->public_path,
            ]);
            $this->folderProtections->resyncIfNeeded($account, $domain->domain);
            $this->siteRedirects->resyncIfNeeded($account, $domain->domain);
            $this->hotlinkProtection->resyncIfNeeded($account, $domain->domain);
            $this->mimeTypes->resyncIfNeeded($account, $domain->domain);
        }

        $account->update(['php_version' => $newVersion]);
    }

    /**
     * Migração ÚNICA pós-deploy (comando `php-fpm:migrate-to-per-account`)
     * pra contas provisionadas ANTES da troca de arquitetura do PHP-FPM
     * (de service compartilhado por versão pra 1 processo dedicado por
     * conta) — não faz parte do ciclo de vida normal. Cria o service
     * novo (caminho novo, não colide com o pool antigo) e re-renderiza
     * o vhost de cada domínio, que passa a apontar pro socket novo
     * (PhpFpmPool::socketPath() já mudou no Agent). Não apaga nada da
     * arquitetura antiga — isso é limpeza manual documentada no
     * deploy/README.md, feita conscientemente depois de confirmar que
     * tudo migrou.
     */
    public function migratePhpFpmArchitecture(HostingAccount $account): void
    {
        $server = $account->server;
        $username = $account->linux_username;
        $phpVersion = $account->php_version;

        $this->runStep($server, 'php.create_pool', array_merge([
            'username' => $username,
            'php_version' => $phpVersion,
        ], $account->php_fpm_settings ? $this->formatPoolSettings($account->php_fpm_settings) : []));

        $appDomains = HostedApp::where('hosting_account_id', $account->id)->pluck('domain')->all();

        if (! in_array($account->primary_domain, $appDomains, true)) {
            $this->runStep($server, 'web.update_vhost_php_version', [
                'username' => $username,
                'domain' => $account->primary_domain,
                'php_version' => $phpVersion,
                'ssl_active' => $account->ssl_status === 'active',
            ]);
        }

        foreach ($account->domains as $domain) {
            if (in_array($domain->domain, $appDomains, true)) {
                continue;
            }

            $this->runStep($server, 'web.update_vhost_php_version', [
                'username' => $username,
                'domain' => $domain->domain,
                'location' => $domain->isOutsidePublicHtml() ? 'outside' : 'inside',
                'subdir' => $domain->subdirectory,
                'php_version' => $phpVersion,
                'ssl_active' => $domain->ssl_status === 'active',
            ]);
        }
    }

    public function updatePhpFpmSettings(HostingAccount $account, array $settings): void
    {
        // Esse formulário não mexe em zend_extensions (fica numa tela
        // própria, ver updateZendExtensions()) — preserva o que já
        // estava salvo, senão salvar as configurações "normais" apagaria
        // silenciosamente a extensão Zend escolhida (o valor ficaria
        // errado em php_fpm_settings mesmo sem o unit ter mudado de
        // verdade, e a próxima troca de versão de PHP reproduziria esse
        // valor errado pro Agent).
        $settings['zend_extensions'] = ($account->php_fpm_settings ?? [])['zend_extensions'] ?? [];

        $this->runStep($account->server, 'php.update_pool_settings', array_merge([
            'username' => $account->linux_username,
            'php_version' => $account->php_version,
        ], $this->formatPoolSettings($settings)));

        $account->update(['php_fpm_settings' => $settings]);
    }

    /**
     * Muda só a extensão Zend (ex.: ioncube_loader) — precisa de uma
     * Action própria no Agent (reinicia o processo, diferente do reload
     * gracioso de updatePhpFpmSettings()), mas reaproveita os MESMOS
     * settings já salvos pros outros tunables (memory_limit,
     * extra_extensions etc.) não resetarem sem querer.
     */
    public function updateZendExtensions(HostingAccount $account, array $zendExtensions): void
    {
        // Conta pode nunca ter salvo os outros tunáveis ainda (settings
        // vazio) — nesse caso não dá pra chamar formatPoolSettings()
        // (espera todas as chaves preenchidas), então manda só
        // zend_extensions e deixa os demais tunáveis caírem no padrão do
        // Agent, mesmo padrão já usado em changePhpVersion().
        $existing = $account->php_fpm_settings ?? [];
        $formatted = $existing ? $this->formatPoolSettings($existing) : [];
        $formatted['zend_extensions'] = implode(',', $zendExtensions);

        $this->runStep($account->server, 'php.update_zend_extensions', array_merge([
            'username' => $account->linux_username,
            'php_version' => $account->php_version,
        ], $formatted));

        $existing['zend_extensions'] = $zendExtensions;
        $account->update(['php_fpm_settings' => $existing]);
    }

    /**
     * @return array<string, string>
     */
    private function formatPoolSettings(array $settings): array
    {
        return [
            'memory_limit' => $settings['memory_limit'].'M',
            'upload_max_filesize' => $settings['upload_max_filesize'].'M',
            'post_max_size' => $settings['post_max_size'].'M',
            'max_execution_time' => (string) $settings['max_execution_time'],
            'max_input_time' => (string) $settings['max_input_time'],
            'max_input_vars' => (string) $settings['max_input_vars'],
            'max_file_uploads' => (string) $settings['max_file_uploads'],
            'session.gc_maxlifetime' => (string) $settings['session_gc_maxlifetime'],
            'display_errors' => ($settings['display_errors'] ?? false) ? 'On' : 'Off',
            'log_errors' => ($settings['log_errors'] ?? true) ? 'On' : 'Off',
            'error_reporting' => self::ERROR_REPORTING_PRESETS[$settings['error_reporting'] ?? 'production'] ?? self::ERROR_REPORTING_PRESETS['production'],
            'file_uploads' => ($settings['file_uploads'] ?? true) ? 'On' : 'Off',
            'short_open_tag' => ($settings['short_open_tag'] ?? false) ? 'On' : 'Off',
            'disable_functions' => implode(',', $settings['disable_functions'] ?? []),
            'extra_extensions' => implode(',', $settings['extra_extensions'] ?? []),
            'zend_extensions' => implode(',', $settings['zend_extensions'] ?? []),
        ];
    }

    public function refreshDiskUsage(HostingAccount $account): void
    {
        $job = $this->client->dispatch($account->server, 'disk.usage', ['username' => $account->linux_username]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao calcular uso de disco.');
        }

        $account->update([
            'disk_usage_mb' => $job->result['used_mb'] ?? null,
            'disk_usage_checked_at' => now(),
        ]);
    }

    /**
     * Assíncrona — só dispara e marca "pending", igual issueSslCertificate().
     * O resultado final (lista de arquivos gerados, ou erro) chega
     * depois via callback do Agent, tratado em
     * AgentWebhookController::callback() — correlacionado pelo id deste
     * HostingBackup embutido no payload (não dá pra usar o domínio como
     * a SSL faz, aqui não tem um).
     */
    public function createBackup(HostingAccount $account): HostingBackup
    {
        // Falhas não contam pra cota (não geram arquivo nenhum) — só
        // pending/completed representam backup de fato armazenado ou em
        // progresso.
        $activeBackups = $account->backups()->whereIn('status', ['pending', 'completed'])->count();

        if ($activeBackups >= $account->plan->max_backups) {
            throw new RuntimeException('Limite de backups do plano atingido ('.$account->plan->max_backups.'). Baixe/apague backups antigos ou aumente o limite do plano.');
        }

        $backup = $account->backups()->create(['status' => 'pending']);

        $this->client->dispatch($account->server, 'backup.create', [
            'username' => $account->linux_username,
            'databases' => $account->databases->pluck('db_name')->all(),
            'retention' => config('hosting.backup_retention'),
            'backup_id' => $backup->id,
        ]);

        return $backup;
    }

    public function deleteBackup(HostingAccount $account, HostingBackup $backup): void
    {
        $filenames = collect($backup->files)->pluck('filename')->filter()->values()->all();

        if ($filenames !== []) {
            $this->runStep($account->server, 'backup.delete', [
                'username' => $account->linux_username,
                'files' => $filenames,
            ]);
        }

        $backup->delete();
    }

    public function suspend(HostingAccount $account): void
    {
        $this->runStep($account->server, 'hosting.suspend', [
            'username' => $account->linux_username,
            'domain' => $account->primary_domain,
        ]);

        $account->update(['status' => 'suspended']);
    }

    public function reactivate(HostingAccount $account): void
    {
        $this->runStep($account->server, 'hosting.reactivate', [
            'username' => $account->linux_username,
            'domain' => $account->primary_domain,
        ]);

        $account->update(['status' => 'active']);
    }

    /**
     * Aplica CPU/RAM/processos/I-O (cgroup, via user-{uid}.slice) e cota
     * de disco (setquota -u) da conta, a partir dos limites do plano
     * atual. Chamada na provisão (best-effort, ver provision()) e pelo
     * botão admin "Reaplicar limites" (sem try/catch — erro real sobe
     * normal pro controller). Ausência de limite no plano = sem limite
     * (infinity/100 pro cgroup, 0 pra cota de disco).
     */
    public function syncResourceLimits(HostingAccount $account): void
    {
        $plan = $account->plan;

        $this->runStep($account->server, 'resources.set_limits', [
            'username' => $account->linux_username,
            'cpu_percent' => $plan->cpu_cores ? (string) ($plan->cpu_cores * 100) : 'infinity',
            'memory_mb' => $plan->memory_limit_mb ? (string) $plan->memory_limit_mb : 'infinity',
            'tasks_max' => $plan->max_processes ? (string) $plan->max_processes : 'infinity',
            'io_weight' => (string) ($plan->io_weight ?? 100),
        ]);

        $this->runStep($account->server, 'disk.sync_quota', [
            'username' => $account->linux_username,
            'quota_mb' => $plan->disk_quota_mb ?? 0,
        ]);
    }

    private function runStep(Server $server, string $action, array $payload): void
    {
        $job = $this->client->dispatch($server, $action, $payload);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? "Falha ao executar {$action}");
        }
    }

    private function rollback(Server $server, array $completedSteps, string $username, string $domain): void
    {
        if (in_array('vhost', $completedSteps, true)) {
            $this->client->dispatch($server, 'web.delete_vhost', ['domain' => $domain]);
        }

        if (in_array('pool', $completedSteps, true)) {
            $this->client->dispatch($server, 'php.delete_pool', ['username' => $username]);
        }

        if (in_array('user', $completedSteps, true)) {
            // Limpa cgroup/cota ANTES de apagar o usuário — o drop-in do
            // "systemctl set-property" e a entrada de "setquota" não são
            // removidos automaticamente pelo userdel, e uma UID reciclada
            // por um useradd futuro herdaria limites de uma conta antiga.
            $this->client->dispatch($server, 'resources.set_limits', [
                'username' => $username,
                'cpu_percent' => 'infinity',
                'memory_mb' => 'infinity',
                'tasks_max' => 'infinity',
                'io_weight' => '100',
            ]);
            $this->client->dispatch($server, 'disk.sync_quota', ['username' => $username, 'quota_mb' => 0]);

            $this->client->dispatch($server, 'linux.delete_user', ['username' => $username]);
        }
    }
}
