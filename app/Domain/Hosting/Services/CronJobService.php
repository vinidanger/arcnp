<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\CronJob;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * O Painel é sempre a fonte da verdade: toda mudança (criar/apagar)
 * reenvia a lista COMPLETA de jobs da conta pro Agent, que reescreve o
 * arquivo de cron inteiro — nunca um diff incremental.
 */
class CronJobService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    public function create(HostingAccount $account, array $data): CronJob
    {
        if ($account->cronJobs()->count() >= $account->plan->max_cron_jobs) {
            throw new RuntimeException('Limite de tarefas cron do plano atingido.');
        }

        $job = $account->cronJobs()->create($data);

        try {
            $this->sync($account);
        } catch (RuntimeException $e) {
            $job->delete();
            throw $e;
        }

        return $job;
    }

    public function delete(CronJob $job): void
    {
        $account = $job->hostingAccount;
        $job->delete();

        $this->sync($account);
    }

    public function sync(HostingAccount $account): void
    {
        $jobs = $account->cronJobs()->get(['minute', 'hour', 'day', 'month', 'weekday', 'command'])->toArray();

        $result = $this->client->dispatch($account->server, 'cron.sync', [
            'username' => $account->linux_username,
            'jobs' => $jobs,
        ]);

        if ($result->status !== 'completed') {
            throw new RuntimeException($result->error ?? 'Falha ao sincronizar cron.');
        }
    }
}
