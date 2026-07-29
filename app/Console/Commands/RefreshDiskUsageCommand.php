<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class RefreshDiskUsageCommand extends Command
{
    protected $signature = 'disk-usage:refresh';

    protected $description = 'Atualiza o uso de disco de cada conta ativa (usado pra aplicar a cota do plano) — roda de hora em hora.';

    public function handle(HostingAccountProvisioningService $provisioning): int
    {
        $accounts = HostingAccount::where('status', 'active')->get();
        $count = 0;

        foreach ($accounts as $account) {
            try {
                $provisioning->refreshDiskUsage($account);
                $count++;
            } catch (Throwable $e) {
                $this->warn("Falha ao atualizar uso de disco da conta {$account->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$count} conta(s) atualizada(s).");

        return self::SUCCESS;
    }
}
