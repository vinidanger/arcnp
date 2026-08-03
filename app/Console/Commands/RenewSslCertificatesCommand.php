<?php

namespace App\Console\Commands;

use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use Illuminate\Console\Command;

class RenewSslCertificatesCommand extends Command
{
    protected $signature = 'ssl:renew';

    protected $description = 'Dispara renovação de certificados Let\'s Encrypt em cada servidor (certbot decide o que precisa renovar) — roda uma vez por dia.';

    public function handle(AgentHttpClient $client): int
    {
        $servers = Server::whereHas('currentCredential')->get();

        foreach ($servers as $server) {
            $client->dispatch($server, 'ssl.renew_all');
        }

        $this->info("Renovação disparada em {$servers->count()} servidor(es).");

        return self::SUCCESS;
    }
}
