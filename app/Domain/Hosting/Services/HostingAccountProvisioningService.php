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

            $this->syncPhpPools($account);
            $completed[] = 'pool';

            $this->runStep($server, 'web.create_vhost', ['username' => $username, 'domain' => $domain, 'php_version' => $phpVersion, 'public_path' => $account->public_path, 'waf_enabled' => $account->waf_enabled ?? false]);
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

        if ($account->db_master_username) {
            $this->client->dispatch($account->server, 'database.delete_master_user', [
                'db_username' => $account->db_master_username,
            ]);
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

        // Nasce com a versão/settings ATUAIS da conta como ponto de
        // partida — depois é editável independente, pela própria tela
        // de PHP desse domínio (ver PhpSettingsController).
        $domain = $account->domains()->create([
            'domain' => $domainName,
            'type' => $type,
            'location' => $location,
            'subdirectory' => $subdir,
            'php_version' => $account->php_version,
            'php_fpm_settings' => $account->php_fpm_settings,
            'status' => 'creating',
        ]);

        try {
            // Precisa existir ANTES do vhost, senão o socket que o vhost
            // referencia não teria processo nenhum escutando nele ainda.
            $this->syncPhpPools($account);

            $this->runStep($account->server, 'web.create_addon_domain', [
                'username' => $account->linux_username,
                'domain' => $domainName,
                'location' => $outside ? 'outside' : 'inside',
                'subdir' => $subdir,
                'php_version' => $domain->php_version,
                'waf_enabled' => $domain->waf_enabled ?? false,
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

        // O domínio já sumiu do banco — o próximo resync (que consulta
        // $account->domains() fresco) naturalmente não inclui mais ele,
        // e o pool dele é removido do lado do Agent.
        $this->syncPhpPools($account);
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

        $this->applyDatabaseUserLimits($account->server, $dbUsername, $account->plan->max_db_connections, $account->plan->max_db_queries_per_hour);

        return $account->databases()->create([
            'db_name' => $dbName,
            'db_username' => $dbUsername,
            'db_password' => $dbPassword,
        ]);
    }

    /**
     * Usuário MySQL "mestre" da conta — sem banco próprio, só um GRANT em
     * curinga sobre "{linux_username}_%" (todos os bancos da conta).
     * Criado sob demanda (não no provisionamento, pra não gerar um usuário
     * MySQL extra em contas que nunca criam banco nenhum) — chamado no
     * momento em que o cliente pede SSO pro phpMyAdmin "todos os bancos".
     * Idempotente: se já existe, só devolve a conta como está.
     */
    public function ensureMasterDatabaseUser(HostingAccount $account): HostingAccount
    {
        if ($account->db_master_username && $account->db_master_password) {
            return $account;
        }

        $dbPassword = Str::password(24);

        $this->runStep($account->server, 'database.create_master_user', [
            'db_prefix' => $account->linux_username,
            'db_username' => $account->linux_username,
            'db_password' => $dbPassword,
        ]);

        $this->applyDatabaseUserLimits($account->server, $account->linux_username, $account->plan->max_db_connections, $account->plan->max_db_queries_per_hour);

        $account->update([
            'db_master_username' => $account->linux_username,
            'db_master_password' => $dbPassword,
        ]);

        return $account->fresh();
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
            'php_version' => $domain ? $domain->php_version : $account->php_version,
            'public_path' => $domain ? $domain->public_path : $account->public_path,
            'waf_enabled' => ($domain ? $domain->waf_enabled : $account->waf_enabled) ?? false,
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
            'waf_enabled' => $account->waf_enabled ?? false,
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
            'php_version' => $domain->php_version,
            'ssl_active' => $domain->ssl_status === 'active',
            'public_path' => $publicPath,
            'waf_enabled' => $domain->waf_enabled ?? false,
        ]);

        $domain->update(['public_path' => $publicPath]);
    }

    /**
     * Liga/desliga o WAF (ModSecurity + OWASP CRS, ver seção 47 do
     * deploy/README.md do Agent) pra um domínio específico — reescreve
     * o vhost preservando tudo mais (SSL, versão de PHP, public_path).
     * $domain null = domínio principal da conta.
     */
    public function updateWafEnabled(HostingAccount $account, ?Domain $domain, bool $enabled): void
    {
        $this->runStep($account->server, 'web.update_document_root', [
            'username' => $account->linux_username,
            'domain' => $domain ? $domain->domain : $account->primary_domain,
            'location' => $domain?->isOutsidePublicHtml() ? 'outside' : 'inside',
            'subdir' => $domain?->subdirectory,
            'php_version' => $domain ? $domain->php_version : $account->php_version,
            'ssl_active' => ($domain ? $domain->ssl_status : $account->ssl_status) === 'active',
            'public_path' => $domain ? $domain->public_path : $account->public_path,
            'waf_enabled' => $enabled,
        ]);

        if ($domain) {
            $domain->update(['waf_enabled' => $enabled]);
        } else {
            $account->update(['waf_enabled' => $enabled]);
        }
    }

    /**
     * PHP por DOMÍNIO, não por conta — $domain null representa o
     * domínio PRINCIPAL (lê/grava direto em $account; ele nunca ganha
     * uma linha na tabela domains, ver plano). As 3 operações abaixo
     * (versão, settings, zend_extensions) todas terminam chamando
     * syncPhpPools(), que resincroniza o estado INTEIRO da conta de
     * uma vez (mesmo domínio que não mudou é reenviado, sem efeito
     * colateral — é assim que o Agent reconcilia servidores/processos).
     */
    public function updateDomainPhpVersion(HostingAccount $account, ?Domain $domain, string $newVersion): void
    {
        if ($domain) {
            if ($domain->php_version === $newVersion) {
                return;
            }

            $domain->update(['php_version' => $newVersion]);
        } else {
            if ($account->php_version === $newVersion) {
                return;
            }

            $account->update(['php_version' => $newVersion]);
        }

        $this->syncPhpPools($account);
    }

    public function updateDomainPhpSettings(HostingAccount $account, ?Domain $domain, array $settings): void
    {
        // Esse formulário não mexe em zend_extensions (fica numa tela
        // própria, ver updateDomainZendExtensions()) — preserva o que já
        // estava salvo pro domínio, senão salvar as configurações
        // "normais" apagaria silenciosamente a extensão Zend escolhida.
        $current = $domain ? $domain->php_fpm_settings : $account->php_fpm_settings;
        $settings['zend_extensions'] = ($current ?? [])['zend_extensions'] ?? [];

        if ($domain) {
            $domain->update(['php_fpm_settings' => $settings]);
        } else {
            $account->update(['php_fpm_settings' => $settings]);
        }

        $this->syncPhpPools($account);
    }

    /**
     * Muda só a extensão Zend (ex.: ioncube_loader) do domínio — reaproveita
     * os MESMOS settings já salvos pros outros tunables (memory_limit,
     * extra_extensions etc.) não resetarem sem querer.
     */
    public function updateDomainZendExtensions(HostingAccount $account, ?Domain $domain, array $zendExtensions): void
    {
        $existing = ($domain ? $domain->php_fpm_settings : $account->php_fpm_settings) ?? [];
        $existing['zend_extensions'] = $zendExtensions;

        if ($domain) {
            $domain->update(['php_fpm_settings' => $existing]);
        } else {
            $account->update(['php_fpm_settings' => $existing]);
        }

        $this->syncPhpPools($account);
    }

    /**
     * Resync COMPLETO dos processos PHP-FPM da conta — monta o estado
     * desejado de TODOS os domínios (principal + adicionais, pulando
     * os que estão em modo app/HostedApp, que não usam PHP-FPM) e
     * manda pro Agent numa chamada só (php.sync_account_pools). Usa
     * $account->domains()->get() (consulta nova, não a relação
     * possivelmente já carregada em memória) — importante logo depois
     * de criar/remover um domínio, senão o resync sairia com a lista
     * desatualizada.
     */
    private function syncPhpPools(HostingAccount $account): void
    {
        $appDomains = HostedApp::where('hosting_account_id', $account->id)->pluck('domain')->all();

        $domains = [];

        if (! in_array($account->primary_domain, $appDomains, true)) {
            $domains[] = [
                'domain' => $account->primary_domain,
                'php_version' => $account->php_version,
                'settings' => $account->php_fpm_settings ? $this->formatPoolSettings($account->php_fpm_settings) : [],
            ];
        }

        foreach ($account->domains()->get() as $domain) {
            if (in_array($domain->domain, $appDomains, true)) {
                continue;
            }

            $domains[] = [
                'domain' => $domain->domain,
                'php_version' => $domain->php_version,
                'settings' => $domain->php_fpm_settings ? $this->formatPoolSettings($domain->php_fpm_settings) : [],
            ];
        }

        $this->runStep($account->server, 'php.sync_account_pools', [
            'username' => $account->linux_username,
            'domains' => $domains,
        ]);
    }

    /**
     * Migração ÚNICA pós-deploy (comando `php-fpm:migrate-to-per-domain-pools`)
     * pra contas provisionadas ANTES do PHP passar a ser por domínio —
     * não faz parte do ciclo de vida normal. syncPhpPools() já cria os
     * processos novos (um por grupo versão+zend_extensions em uso,
     * caminho novo, não colide com o processo antigo "arcnp-php-
     * {username}.service" sem sufixo de grupo); falta só re-renderizar
     * o vhost de cada domínio pra apontar pro socket novo
     * (PhpFpmPool::socketPath() mudou de "por conta" pra "por
     * domínio"). Não apaga nada da arquitetura antiga — isso é limpeza
     * manual documentada no deploy/README.md, feita conscientemente
     * depois de confirmar que tudo migrou.
     */
    public function migrateToPerDomainPools(HostingAccount $account): void
    {
        $this->syncPhpPools($account);

        $server = $account->server;
        $username = $account->linux_username;
        $appDomains = HostedApp::where('hosting_account_id', $account->id)->pluck('domain')->all();

        if (! in_array($account->primary_domain, $appDomains, true)) {
            $this->runStep($server, 'web.update_vhost_php_version', [
                'username' => $username,
                'domain' => $account->primary_domain,
                'php_version' => $account->php_version,
                'ssl_active' => $account->ssl_status === 'active',
                'public_path' => $account->public_path,
                'waf_enabled' => $account->waf_enabled ?? false,
            ]);
            // Reescrever o vhost do zero (stub) apaga qualquer bloco
            // customizado injetado nele — reinjeta os que existirem.
            $this->folderProtections->resyncIfNeeded($account, $account->primary_domain);
            $this->siteRedirects->resyncIfNeeded($account, $account->primary_domain);
            $this->hotlinkProtection->resyncIfNeeded($account, $account->primary_domain);
            $this->mimeTypes->resyncIfNeeded($account, $account->primary_domain);
        }

        foreach ($account->domains()->get() as $domain) {
            if (in_array($domain->domain, $appDomains, true)) {
                continue;
            }

            $this->runStep($server, 'web.update_vhost_php_version', [
                'username' => $username,
                'domain' => $domain->domain,
                'location' => $domain->isOutsidePublicHtml() ? 'outside' : 'inside',
                'subdir' => $domain->subdirectory,
                'php_version' => $domain->php_version,
                'ssl_active' => $domain->ssl_status === 'active',
                'public_path' => $domain->public_path,
                'waf_enabled' => $domain->waf_enabled ?? false,
            ]);
            $this->folderProtections->resyncIfNeeded($account, $domain->domain);
            $this->siteRedirects->resyncIfNeeded($account, $domain->domain);
            $this->hotlinkProtection->resyncIfNeeded($account, $domain->domain);
            $this->mimeTypes->resyncIfNeeded($account, $domain->domain);
        }
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

        // Limite é por USUÁRIO MySQL, não por conta — uma conta com N
        // bancos tem N usuários (mais o mestre, se já existir), então
        // reaplica em todos, não só num lugar central.
        foreach ($account->databases as $database) {
            $this->applyDatabaseUserLimits($account->server, $database->db_username, $plan->max_db_connections, $plan->max_db_queries_per_hour);
        }

        if ($account->db_master_username) {
            $this->applyDatabaseUserLimits($account->server, $account->db_master_username, $plan->max_db_connections, $plan->max_db_queries_per_hour);
        }
    }

    private function applyDatabaseUserLimits(Server $server, string $dbUsername, ?int $maxConnections, ?int $maxQueriesPerHour): void
    {
        $this->runStep($server, 'database.set_user_limits', [
            'db_username' => $dbUsername,
            'max_connections' => $maxConnections ?? 0,
            'max_queries_per_hour' => $maxQueriesPerHour ?? 0,
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
            // Lista de domínios vazia = desprovisiona TODOS os processos
            // PHP-FPM da conta (o Agent descobre e remove qualquer grupo
            // versão+zend_extensions que sobrar sem nenhum domínio).
            $this->client->dispatch($server, 'php.sync_account_pools', ['username' => $username, 'domains' => []]);
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
