<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\AppInstallation;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orquestra o instalador de apps (item 15 do roadmap). WordPress é
 * assíncrono (download + wp-cli podem demorar — ver InstallWordPressAction
 * no Agent); o app genérico é síncrono porque reaproveita só ações que
 * já existem (upload + extract + delete do zip temporário).
 */
class AppInstallerService
{
    public function __construct(
        private AgentHttpClient $client,
        private HostingAccountProvisioningService $provisioning,
    ) {
    }

    public function installWordPress(
        HostingAccount $account,
        string $domain,
        string $path,
        string $siteTitle,
        string $adminUser,
        string $adminPassword,
        string $adminEmail,
    ): AppInstallation {
        [$location, $subdir, $sslActive] = $this->resolveLocation($account, $domain, $path);
        $catalog = config('app_catalog.wordpress');

        $database = $this->provisioning->provisionDatabase($account, 'wp');

        $installation = $account->appInstallations()->create([
            'domain' => $domain,
            'path' => $path,
            'catalog_slug' => 'wordpress',
            'status' => 'installing',
            'database_id' => $database->id,
        ]);

        try {
            $job = $this->client->dispatch($account->server, 'app.install_wordpress', [
                'username' => $account->linux_username,
                'domain' => $domain,
                'location' => $location,
                'subdir' => $subdir,
                'ssl_active' => $sslActive,
                'download_url' => $catalog['download_url'],
                'db_name' => $database->db_name,
                'db_username' => $database->db_username,
                'db_password' => $database->db_password,
                'admin_user' => $adminUser,
                'admin_password' => $adminPassword,
                'admin_email' => $adminEmail,
                'site_title' => $siteTitle,
                'app_installation_id' => $installation->id,
            ]);

            // Dispatch assíncrono só falha aqui por erro de rede/conexão
            // com o Agent (não pelo resultado da instalação em si, que
            // chega depois via callback) — mesmo raciocínio de
            // issueSslCertificate()/createBackup().
            if ($job->status === 'failed') {
                throw new RuntimeException($job->error ?? 'Falha ao disparar a instalação.');
            }
        } catch (Throwable $e) {
            $this->provisioning->deleteDatabase($database);
            $installation->delete();
            throw $e;
        }

        return $installation;
    }

    public function installGenericZip(HostingAccount $account, string $domain, string $path, string $zipContent): AppInstallation
    {
        [$location, $subdir] = $this->resolveLocation($account, $domain, $path);
        $root = $location === 'outside' ? $domain : null;
        $tmpName = ($subdir ? "{$subdir}/" : '')."_install_".Str::uuid().'.zip';

        $this->client->uploadFile($account->server, $account->linux_username, $tmpName, $zipContent, $root);

        try {
            $extractJob = $this->client->dispatch($account->server, 'files.extract', [
                'username' => $account->linux_username,
                'root' => $root,
                'path' => $tmpName,
                'dest' => $subdir ?? '',
            ]);

            if ($extractJob->status !== 'completed') {
                throw new RuntimeException($extractJob->error ?? 'Falha ao extrair o app.');
            }
        } finally {
            // Best-effort — o zip temporário não pode ficar exposto
            // dentro do document root de qualquer jeito, sucesso ou falha.
            $this->client->dispatch($account->server, 'files.delete', [
                'username' => $account->linux_username,
                'root' => $root,
                'path' => $tmpName,
            ]);
        }

        return $account->appInstallations()->create([
            'domain' => $domain,
            'path' => $path,
            'catalog_slug' => 'generic_zip',
            'status' => 'active',
            'installed_at' => now(),
        ]);
    }

    public function delete(AppInstallation $installation): void
    {
        $account = $installation->hostingAccount;
        [$location, $subdir] = $this->resolveLocation($account, $installation->domain, $installation->path);
        $root = $location === 'outside' ? $installation->domain : null;

        $this->client->dispatch($account->server, 'files.delete', [
            'username' => $account->linux_username,
            'root' => $root,
            'path' => $subdir ?? '',
        ]);

        if ($installation->database_id) {
            $this->provisioning->deleteDatabase($installation->database);
        }

        $installation->delete();
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: bool} [location, subdir combinado, ssl_active]
     */
    private function resolveLocation(HostingAccount $account, string $domain, string $installPath): array
    {
        $installPath = trim($installPath, '/');

        if ($domain === $account->primary_domain) {
            return [null, $installPath !== '' ? $installPath : null, $account->ssl_status === 'active'];
        }

        $addon = $account->domains()->where('domain', $domain)->firstOrFail();

        if ($addon->isOutsidePublicHtml()) {
            return ['outside', $installPath !== '' ? $installPath : null, $addon->ssl_status === 'active'];
        }

        $combined = trim(($addon->subdirectory ?: '').'/'.$installPath, '/');

        return [null, $combined !== '' ? $combined : null, $addon->ssl_status === 'active'];
    }
}
