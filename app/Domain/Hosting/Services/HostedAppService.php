<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostedApp;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Cria a linha ANTES de disparar pro Agent (precisa do id pra nomear o
 * unit systemd, arcnp-app-{id}.service) e desfaz (delete) se o Agent
 * falhar — mesmo padrão de compensação do resto do painel, só que na
 * ordem inversa de FtpAccountService porque aqui o id é um input do
 * payload, não só um registro pós-fato.
 */
class HostedAppService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function create(HostingAccount $account, string $domain, string $runtime, string $entryFile): HostedApp
    {
        [$location, $subdir, $sslActive, $wafEnabled] = $this->domainContext($account, $domain);

        $port = (int) (HostedApp::where('server_id', $account->server_id)->max('port') ?? 19999) + 1;

        $app = $account->hostedApps()->create([
            'server_id' => $account->server_id,
            'domain' => $domain,
            'runtime' => $runtime,
            'entry_file' => $entryFile,
            'port' => $port,
        ]);

        try {
            $job = $this->client->dispatch($account->server, 'app.create', [
                'app_id' => $app->id,
                'username' => $account->linux_username,
                'domain' => $domain,
                'location' => $location,
                'subdir' => $subdir,
                'runtime' => $runtime,
                'entry_file' => $entryFile,
                'port' => $port,
                'ssl_active' => $sslActive,
                'waf_enabled' => $wafEnabled,
                'http3_enabled' => $account->server->http3_enabled ?? false,
            ]);

            if ($job->status !== 'completed') {
                throw new RuntimeException($job->error ?? 'Falha ao criar o app.');
            }
        } catch (RuntimeException $e) {
            $app->delete();
            throw $e;
        }

        return $app;
    }

    public function delete(HostedApp $app): void
    {
        $account = $app->hostingAccount;
        [$location, $subdir, $sslActive, $wafEnabled] = $this->domainContext($account, $app->domain);

        $job = $this->client->dispatch($app->server, 'app.delete', [
            'app_id' => $app->id,
            'username' => $account->linux_username,
            'domain' => $app->domain,
            'location' => $location,
            'subdir' => $subdir,
            'php_version' => $account->php_version,
            'ssl_active' => $sslActive,
            'waf_enabled' => $wafEnabled,
            'http3_enabled' => $account->server->http3_enabled ?? false,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao remover o app.');
        }

        $app->delete();
    }

    public function restart(HostedApp $app): void
    {
        $job = $this->client->dispatch($app->server, 'app.restart', ['app_id' => $app->id]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao reiniciar o app.');
        }
    }

    /**
     * Nunca persistido — sempre consultado ao vivo (mesmo raciocínio do
     * disk_usage: um status salvo fica velho e mentiroso).
     */
    public function status(HostedApp $app): string
    {
        $job = $this->client->dispatch($app->server, 'app.status', ['app_id' => $app->id]);

        if ($job->status !== 'completed') {
            return 'unknown';
        }

        return $job->result['status'] ?? 'unknown';
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: bool, 3: bool} [location, subdir, ssl_active, waf_enabled]
     */
    private function domainContext(HostingAccount $account, string $domain): array
    {
        if ($domain === $account->primary_domain) {
            return [null, null, $account->ssl_status === 'active', $account->waf_enabled ?? false];
        }

        $addon = $account->domains()->where('domain', $domain)->firstOrFail();

        return [
            $addon->isOutsidePublicHtml() ? 'outside' : 'inside',
            $addon->subdirectory,
            $addon->ssl_status === 'active',
            $addon->waf_enabled ?? false,
        ];
    }
}
