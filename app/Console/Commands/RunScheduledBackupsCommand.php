<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use Illuminate\Console\Command;

class RunScheduledBackupsCommand extends Command
{
    protected $signature = 'backups:run-scheduled';

    protected $description = 'Dispara backup pras contas cuja frequência configurada já venceu (roda de hora em hora, ver routes/console.php).';

    /**
     * Marca last_backup_at no momento do disparo, não da conclusão —
     * senão um backup demorado faria o comando disparar outro em cima
     * antes do primeiro terminar, na próxima execução horária.
     */
    private const INTERVALS = [
        'daily' => 1,
        'weekly' => 7,
    ];

    public function handle(HostingAccountProvisioningService $provisioning): int
    {
        $count = 0;

        foreach (self::INTERVALS as $frequency => $days) {
            $accounts = HostingAccount::where('status', 'active')
                ->where('backup_frequency', $frequency)
                ->where(function ($query) use ($days) {
                    $query->whereNull('last_backup_at')
                        ->orWhere('last_backup_at', '<=', now()->subDays($days));
                })
                ->get();

            foreach ($accounts as $account) {
                $provisioning->createBackup($account);
                $account->update(['last_backup_at' => now()]);
                $count++;
            }
        }

        if ($count > 0) {
            $this->info("{$count} backup(s) agendado(s) disparado(s).");
        }

        return self::SUCCESS;
    }
}
