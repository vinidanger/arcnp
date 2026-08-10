<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\DomainTrafficStat;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Precisa rodar ANTES do horário de rotação do log (ver logrotate
 * documentado no deploy/README.md do Agent, seção nova) — lê o
 * access.log do dia inteiro de uma vez, sem rastrear offset/checkpoint.
 */
class CollectTrafficStatsCommand extends Command
{
    protected $signature = 'traffic:collect';

    protected $description = 'Coleta estatísticas agregadas de tráfego (hits/visitantes únicos/páginas mais vistas) de cada domínio de conta ativa — roda 1x/dia, antes da rotação do log.';

    public function handle(AgentHttpClient $client): int
    {
        $accounts = HostingAccount::where('status', 'active')->with('domains')->get();
        $today = now()->toDateString();
        $count = 0;

        foreach ($accounts as $account) {
            $domains = array_merge([$account->primary_domain], $account->domains->pluck('domain')->all());

            foreach ($domains as $domain) {
                try {
                    $job = $client->dispatch($account->server, 'web.analyze_traffic', ['domain' => $domain]);

                    if ($job->status !== 'completed') {
                        $this->warn("Falha ao coletar tráfego de {$domain}: ".($job->error ?? 'erro desconhecido'));

                        continue;
                    }

                    DomainTrafficStat::updateOrCreate(
                        ['domain' => $domain, 'date' => $today],
                        [
                            'hosting_account_id' => $account->id,
                            'hits' => $job->result['hits'] ?? 0,
                            'unique_visitors' => $job->result['unique_visitors'] ?? 0,
                            'top_paths' => $job->result['top_paths'] ?? [],
                            'status_counts' => $job->result['status_counts'] ?? [],
                        ]
                    );
                    $count++;
                } catch (Throwable $e) {
                    $this->warn("Falha ao coletar tráfego de {$domain}: {$e->getMessage()}");
                }
            }
        }

        $this->info("{$count} domínio(s) coletado(s).");

        return self::SUCCESS;
    }
}
