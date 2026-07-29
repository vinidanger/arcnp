<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
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
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function provision(HostingAccount $account): void
    {
        $server = $account->server;
        $username = $account->linux_username;
        $domain = $account->primary_domain;

        $completed = [];

        try {
            $this->runStep($server, 'linux.create_user', ['username' => $username]);
            $completed[] = 'user';

            $this->runStep($server, 'php.create_pool', ['username' => $username]);
            $completed[] = 'pool';

            $this->runStep($server, 'web.create_vhost', ['username' => $username, 'domain' => $domain]);
            $completed[] = 'vhost';

            $account->update(['status' => 'active', 'last_provision_error' => null]);
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
        $this->rollback($account->server, ['vhost', 'pool', 'user'], $account->linux_username, $account->primary_domain);
    }

    private function runStep(Server $server, string $action, array $payload): void
    {
        $job = $this->client->dispatch($server, $action, $payload);

        if ($job->status !== 'completed') {
            throw new \RuntimeException($job->error ?? "Falha ao executar {$action}");
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
            $this->client->dispatch($server, 'linux.delete_user', ['username' => $username]);
        }
    }
}
