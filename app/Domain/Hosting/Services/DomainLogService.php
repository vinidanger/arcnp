<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Só leitura — nenhum estado gravado no Painel, cada chamada busca o
 * log direto no Agent na hora (ver TailDomainLogAction).
 */
class DomainLogService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function tail(HostingAccount $account, string $domain, string $type, int $lines): string
    {
        $job = $this->client->dispatch($account->server, 'web.tail_domain_log', [
            'domain' => $domain,
            'type' => $type,
            'lines' => $lines,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao ler o log.');
        }

        return $job->result['content'] ?? '';
    }
}
