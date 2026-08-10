<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\MailDomain;
use App\Domain\Hosting\Services\EmailHealthService;
use App\Domain\Servers\Models\Server;
use Illuminate\Console\Command;
use Throwable;

class CheckEmailHealthCommand extends Command
{
    protected $signature = 'email:check-health';

    protected $description = 'Checa SPF/DKIM/DMARC de cada domínio de e-mail e PTR/blacklist de cada servidor — roda uma vez por dia.';

    public function handle(EmailHealthService $service): int
    {
        $serverCount = 0;

        foreach (Server::whereNotNull('mail_hostname')->get() as $server) {
            try {
                $service->checkServer($server);
                $serverCount++;
            } catch (Throwable $e) {
                $this->warn("Falha ao checar servidor {$server->id}: {$e->getMessage()}");
            }
        }

        $domainCount = 0;

        foreach (MailDomain::all() as $mailDomain) {
            try {
                $service->checkMailDomain($mailDomain);
                $domainCount++;
            } catch (Throwable $e) {
                $this->warn("Falha ao checar domínio de e-mail {$mailDomain->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$serverCount} servidor(es) e {$domainCount} domínio(s) de e-mail checados.");

        return self::SUCCESS;
    }
}
