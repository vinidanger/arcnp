<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * Só leitura — mesmo padrão do DomainLogService, mas o log do Postfix
 * é do SERVIDOR inteiro (não por conta), então busca por endereço é o
 * que de fato restringe o resultado ao que interessa pro cliente/admin
 * dessa conta (ver TailMailLogAction).
 */
class MailLogService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function tail(HostingAccount $account, int $lines, ?string $search): string
    {
        $job = $this->client->dispatch($account->server, 'mail.tail_log', [
            'lines' => $lines,
            'search' => $search,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao ler o log de e-mail.');
        }

        return $job->result['content'] ?? '';
    }
}
