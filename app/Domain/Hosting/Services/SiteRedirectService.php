<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\SiteRedirect;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Mesmo padrão de FolderProtectionService: o Agent não regenera o
 * vhost inteiro, só o trecho entre marcadores (ver SyncRedirectsAction)
 * — mas o Painel ainda reenvia o estado COMPLETO dos redirecionamentos
 * daquele domínio a cada mutação, é o Agent quem decide o que sobra.
 */
class SiteRedirectService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function create(HostingAccount $account, string $domain, string $path, string $destination, int $statusCode): SiteRedirect
    {
        $redirect = $account->siteRedirects()->create([
            'domain' => $domain,
            'path' => $path,
            'destination' => $destination,
            'status_code' => $statusCode,
        ]);

        try {
            $this->syncState($account, $domain);
        } catch (RuntimeException $e) {
            $redirect->delete();
            throw $e;
        }

        return $redirect;
    }

    public function delete(SiteRedirect $redirect): void
    {
        $account = $redirect->hostingAccount;
        $domain = $redirect->domain;
        $redirect->delete();

        $this->syncState($account, $domain);
    }

    /**
     * Chamado depois de qualquer ação que regenere o vhost inteiro a
     * partir do stub (troca de versão de PHP, emissão de SSL) — mesmo
     * motivo do FolderProtectionService::resyncIfNeeded().
     */
    public function resyncIfNeeded(HostingAccount $account, string $domain): void
    {
        if ($account->siteRedirects()->where('domain', $domain)->exists()) {
            $this->syncState($account, $domain);
        }
    }

    private function syncState(HostingAccount $account, string $domain): void
    {
        $redirects = $account->siteRedirects()
            ->where('domain', $domain)
            ->get()
            ->map(fn (SiteRedirect $r) => [
                'path' => $r->path,
                'destination' => $r->destination,
                'status_code' => $r->status_code,
            ])
            ->all();

        $job = $this->client->dispatch($account->server, 'web.sync_redirects', [
            'domain' => $domain,
            'redirects' => $redirects,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar redirecionamentos.');
        }
    }
}
