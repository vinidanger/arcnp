<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * PHP por domínio, não por conta: todo método aceita um $domain
 * opcional — null representa o domínio PRINCIPAL (lê/grava direto na
 * $account, mesmas rotas de sempre, sem quebrar link/bookmark
 * existente); não-null é um domínio adicional/subdomínio, resolvido
 * via as rotas novas "hosting-accounts/{account}/domains/{domain}/php...".
 * Os 4 métodos públicos "antigos" (index/updateVersion/updateSettings/
 * updateZendExtensions) e os 4 novos "ForDomain" são wrappers finos em
 * cima da MESMA lógica privada.
 */
class PhpSettingsController extends Controller
{
    public function index(HostingAccount $hosting_account, AgentHttpClient $client)
    {
        return $this->showIndex($hosting_account, null, $client);
    }

    public function indexForDomain(HostingAccount $hosting_account, Domain $domain, AgentHttpClient $client)
    {
        return $this->showIndex($hosting_account, $domain, $client);
    }

    public function updateVersion(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning)
    {
        return $this->applyVersion($request, $hosting_account, null, $provisioning);
    }

    public function updateVersionForDomain(Request $request, HostingAccount $hosting_account, Domain $domain, HostingAccountProvisioningService $provisioning)
    {
        return $this->applyVersion($request, $hosting_account, $domain, $provisioning);
    }

    public function updateSettings(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning, AgentHttpClient $client)
    {
        return $this->applySettings($request, $hosting_account, null, $provisioning, $client);
    }

    public function updateSettingsForDomain(Request $request, HostingAccount $hosting_account, Domain $domain, HostingAccountProvisioningService $provisioning, AgentHttpClient $client)
    {
        return $this->applySettings($request, $hosting_account, $domain, $provisioning, $client);
    }

    public function updateZendExtensions(Request $request, HostingAccount $hosting_account, HostingAccountProvisioningService $provisioning, AgentHttpClient $client)
    {
        return $this->applyZendExtensions($request, $hosting_account, null, $provisioning, $client);
    }

    public function updateZendExtensionsForDomain(Request $request, HostingAccount $hosting_account, Domain $domain, HostingAccountProvisioningService $provisioning, AgentHttpClient $client)
    {
        return $this->applyZendExtensions($request, $hosting_account, $domain, $provisioning, $client);
    }

    private function showIndex(HostingAccount $hosting_account, ?Domain $domain, AgentHttpClient $client)
    {
        $this->authorize('view', $hosting_account);
        $this->ensureDomainBelongsToAccount($hosting_account, $domain);

        $phpVersion = $this->resolvePhpVersion($hosting_account, $domain);
        $extensions = $this->fetchExtensionsInfo($hosting_account, $phpVersion, $client);

        return view('admin.hosting-accounts.php.index', [
            'account' => $hosting_account,
            'domain' => $domain,
            'phpVersion' => $phpVersion,
            'settings' => $this->resolvePhpFpmSettings($hosting_account, $domain),
            'availableExtensions' => $extensions['available'],
            'activeExtensions' => $extensions['active'],
            'availableZendExtensions' => $extensions['available_zend'],
            'activeZendExtensions' => $extensions['active_zend'],
        ]);
    }

    private function applyVersion(Request $request, HostingAccount $hosting_account, ?Domain $domain, HostingAccountProvisioningService $provisioning)
    {
        $this->authorize('update', $hosting_account);
        $this->ensureDomainBelongsToAccount($hosting_account, $domain);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para trocar a versão de PHP.');
        }

        $data = $request->validate([
            'php_version' => ['required', Rule::in(config('hosting.php_versions'))],
        ]);

        try {
            $provisioning->updateDomainPhpVersion($hosting_account, $domain, $data['php_version']);

            return back()->with('status', 'Versão de PHP alterada para '.$data['php_version'].'.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao trocar versão de PHP: '.$e->getMessage());
        }
    }

    private function applySettings(Request $request, HostingAccount $hosting_account, ?Domain $domain, HostingAccountProvisioningService $provisioning, AgentHttpClient $client)
    {
        $this->authorize('update', $hosting_account);
        $this->ensureDomainBelongsToAccount($hosting_account, $domain);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para ajustar configurações de PHP.');
        }

        $phpVersion = $this->resolvePhpVersion($hosting_account, $domain);

        $data = $request->validate([
            'memory_limit' => ['required', 'integer', 'min:32', 'max:2048'],
            'upload_max_filesize' => ['required', 'integer', 'min:1', 'max:2048'],
            'post_max_size' => ['required', 'integer', 'min:1', 'max:2048', 'gte:upload_max_filesize'],
            'max_execution_time' => ['required', 'integer', 'min:1', 'max:300'],
            'max_input_time' => ['required', 'integer', 'min:1', 'max:300'],
            'max_input_vars' => ['required', 'integer', 'min:100', 'max:10000'],
            'max_file_uploads' => ['required', 'integer', 'min:1', 'max:100'],
            'session_gc_maxlifetime' => ['required', 'integer', 'min:60', 'max:86400'],
            'error_reporting' => ['required', Rule::in(array_keys(config('hosting.error_reporting_presets')))],
            'disable_functions' => ['nullable', 'array'],
            'disable_functions.*' => [Rule::in(config('hosting.disablable_php_functions'))],
            'extra_extensions' => ['nullable', 'array'],
            'extra_extensions.*' => [Rule::in($this->fetchExtensionsInfo($hosting_account, $phpVersion, $client)['available'])],
        ]);

        $data['display_errors'] = $request->boolean('display_errors');
        $data['log_errors'] = $request->boolean('log_errors');
        $data['file_uploads'] = $request->boolean('file_uploads');
        $data['short_open_tag'] = $request->boolean('short_open_tag');
        $data['disable_functions'] = $data['disable_functions'] ?? [];
        $data['extra_extensions'] = $data['extra_extensions'] ?? [];

        try {
            $provisioning->updateDomainPhpSettings($hosting_account, $domain, $data);

            return back()->with('status', 'Configurações de PHP atualizadas.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar configurações de PHP: '.$e->getMessage());
        }
    }

    private function applyZendExtensions(Request $request, HostingAccount $hosting_account, ?Domain $domain, HostingAccountProvisioningService $provisioning, AgentHttpClient $client)
    {
        $this->authorize('update', $hosting_account);
        $this->ensureDomainBelongsToAccount($hosting_account, $domain);

        if ($hosting_account->status !== 'active') {
            return back()->with('error', 'A conta precisa estar ativa para ajustar extensões Zend.');
        }

        $phpVersion = $this->resolvePhpVersion($hosting_account, $domain);

        $data = $request->validate([
            'zend_extensions' => ['nullable', 'array'],
            'zend_extensions.*' => [Rule::in($this->fetchExtensionsInfo($hosting_account, $phpVersion, $client)['available_zend'])],
        ]);

        try {
            $provisioning->updateDomainZendExtensions($hosting_account, $domain, $data['zend_extensions'] ?? []);

            return back()->with('status', 'Extensões Zend atualizadas. O PHP desse domínio foi reiniciado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar extensões Zend: '.$e->getMessage());
        }
    }

    private function resolvePhpVersion(HostingAccount $account, ?Domain $domain): string
    {
        return $domain ? $domain->php_version : $account->php_version;
    }

    private function resolvePhpFpmSettings(HostingAccount $account, ?Domain $domain): array
    {
        $settings = $domain ? $domain->php_fpm_settings : $account->php_fpm_settings;

        return $settings ?? config('hosting.default_pool_settings');
    }

    private function ensureDomainBelongsToAccount(HostingAccount $account, ?Domain $domain): void
    {
        abort_unless($domain === null || $domain->hosting_account_id === $account->id, 404);
    }

    /**
     * "available": extensões que o domínio PODE ativar no próprio pool —
     * só as que existem no servidor, não são zend_extension (essas
     * nunca podem ser por pool comum, ver PhpFpmPoolSettings no Agent) e
     * ainda não estão ativas pra todo mundo. "active": as que JÁ estão
     * ativas a nível de servidor (qualquer tipo, inclusive zend) — só
     * informativo, o domínio já tem acesso sem precisar de opt-in, não
     * dá pra desativar por domínio (afetaria todo o servidor).
     * "available_zend"/"active_zend": mesma lógica, só que pro
     * subconjunto zend_extension (ioncube_loader etc.) — essas SIM
     * podem ser opt-in por domínio (ver SyncAccountPhpPoolsAction no
     * Agent). Consulta ao vivo, nunca cacheada — a lista muda conforme
     * o que o admin instala no servidor. $phpVersion é do DOMÍNIO
     * sendo editado, não necessariamente o da conta.
     *
     * @return array{available: list<string>, active: list<string>, available_zend: list<string>, active_zend: list<string>}
     */
    private function fetchExtensionsInfo(HostingAccount $hosting_account, string $phpVersion, AgentHttpClient $client): array
    {
        $job = $client->dispatch($hosting_account->server, 'php.list_extensions', [
            'php_version' => $phpVersion,
        ]);

        if ($job->status !== 'completed') {
            return ['available' => [], 'active' => [], 'available_zend' => [], 'active_zend' => []];
        }

        $extensions = $job->result['extensions'] ?? [];

        return [
            'available' => array_values(array_map(
                fn (array $ext) => $ext['name'],
                array_filter($extensions, fn (array $ext) => $ext['type'] === 'extension' && ! $ext['enabled'])
            )),
            'active' => array_values(array_map(
                fn (array $ext) => $ext['name'],
                array_filter($extensions, fn (array $ext) => $ext['enabled'])
            )),
            'available_zend' => array_values(array_map(
                fn (array $ext) => $ext['name'],
                array_filter($extensions, fn (array $ext) => $ext['type'] === 'zend' && ! $ext['enabled'])
            )),
            'active_zend' => array_values(array_map(
                fn (array $ext) => $ext['name'],
                array_filter($extensions, fn (array $ext) => $ext['type'] === 'zend' && $ext['enabled'])
            )),
        ];
    }
}
