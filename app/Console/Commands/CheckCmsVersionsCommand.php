<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\AppInstallation;
use App\Domain\Hosting\Services\AppInstallerService;
use Illuminate\Console\Command;
use Throwable;

class CheckCmsVersionsCommand extends Command
{
    protected $signature = 'security:check-cms-versions';

    protected $description = 'Compara a versão instalada de cada WordPress ativo com a mais recente do wordpress.org — roda uma vez por dia.';

    public function handle(AppInstallerService $installer): int
    {
        $installations = AppInstallation::where('catalog_slug', 'wordpress')->where('status', 'active')->get();
        $count = 0;

        foreach ($installations as $installation) {
            try {
                $installer->checkVersion($installation);
                $count++;
            } catch (Throwable $e) {
                $this->warn("Falha ao checar versão da instalação {$installation->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$count} instalação(ões) verificada(s).");

        return self::SUCCESS;
    }
}
