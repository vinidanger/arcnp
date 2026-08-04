<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\MimeTypeRule;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Mesmo padrão de SiteRedirectService: o Agent só reescreve o trecho
 * entre marcadores do vhost (ver SyncMimeTypesAction), mas o Painel
 * ainda reenvia o estado COMPLETO das regras daquele domínio a cada
 * mutação.
 */
class MimeTypeService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function create(HostingAccount $account, string $domain, string $extension, string $mimeType): MimeTypeRule
    {
        $rule = $account->mimeTypeRules()->create([
            'domain' => $domain,
            'extension' => $extension,
            'mime_type' => $mimeType,
        ]);

        try {
            $this->syncState($account, $domain);
        } catch (RuntimeException $e) {
            $rule->delete();
            throw $e;
        }

        return $rule;
    }

    public function delete(MimeTypeRule $rule): void
    {
        $account = $rule->hostingAccount;
        $domain = $rule->domain;
        $rule->delete();

        $this->syncState($account, $domain);
    }

    /**
     * Chamado depois de qualquer ação que regenere o vhost inteiro a
     * partir do stub (troca de versão de PHP, emissão de SSL) — mesmo
     * motivo do FolderProtectionService::resyncIfNeeded().
     */
    public function resyncIfNeeded(HostingAccount $account, string $domain): void
    {
        if ($account->mimeTypeRules()->where('domain', $domain)->exists()) {
            $this->syncState($account, $domain);
        }
    }

    private function syncState(HostingAccount $account, string $domain): void
    {
        $rules = $account->mimeTypeRules()
            ->where('domain', $domain)
            ->get()
            ->map(fn (MimeTypeRule $r) => [
                'extension' => $r->extension,
                'mime_type' => $r->mime_type,
            ])
            ->all();

        $job = $this->client->dispatch($account->server, 'web.sync_mime_types', [
            'domain' => $domain,
            'rules' => $rules,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar MIME types.');
        }
    }
}
