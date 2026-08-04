<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HotlinkProtection;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Diferente de proteção de pasta e redirecionamentos (listas de
 * regras), hotlink é liga/desliga por domínio — uma linha só por
 * (conta, domínio), atualizada com updateOrCreate. O Agent também não
 * regenera o vhost inteiro, só o trecho entre marcadores (ver
 * SyncHotlinkProtectionAction).
 */
class HotlinkProtectionService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    /** @param list<string> $extensions @param list<string> $allowedReferrers */
    public function update(HostingAccount $account, string $domain, bool $enabled, array $extensions, array $allowedReferrers): HotlinkProtection
    {
        $existing = $account->hotlinkProtections()->where('domain', $domain)->first();
        $original = $existing?->only(['enabled', 'extensions', 'allowed_referrers']);

        $protection = $account->hotlinkProtections()->updateOrCreate(
            ['domain' => $domain],
            ['enabled' => $enabled, 'extensions' => $extensions, 'allowed_referrers' => $allowedReferrers]
        );

        try {
            $this->syncState($account, $domain);
        } catch (RuntimeException $e) {
            if ($original) {
                $protection->update($original);
            } else {
                $protection->delete();
            }

            throw $e;
        }

        return $protection;
    }

    /**
     * Chamado depois de qualquer ação que regenere o vhost inteiro a
     * partir do stub (troca de versão de PHP, emissão de SSL) — mesmo
     * motivo do FolderProtectionService::resyncIfNeeded().
     */
    public function resyncIfNeeded(HostingAccount $account, string $domain): void
    {
        if ($account->hotlinkProtections()->where('domain', $domain)->where('enabled', true)->exists()) {
            $this->syncState($account, $domain);
        }
    }

    private function syncState(HostingAccount $account, string $domain): void
    {
        $protection = $account->hotlinkProtections()->where('domain', $domain)->first();

        $payload = [
            'domain' => $domain,
            'enabled' => $protection?->enabled ?? false,
            'extensions' => $protection?->extensions ?? [],
            'allowed_referrers' => $protection?->allowed_referrers ?? [],
        ];

        $job = $this->client->dispatch($account->server, 'web.sync_hotlink_protection', $payload);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar proteção hotlink.');
        }
    }
}
