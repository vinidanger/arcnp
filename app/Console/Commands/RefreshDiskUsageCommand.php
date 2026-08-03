<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Models\User;
use App\Notifications\DiskQuotaWarningNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RefreshDiskUsageCommand extends Command
{
    protected $signature = 'disk-usage:refresh';

    protected $description = 'Atualiza o uso de disco de cada conta ativa (usado pra aplicar a cota do plano) — roda de hora em hora.';

    /**
     * % da cota a partir do qual avisa. disk_alert_sent_at evita
     * reenviar toda hora — só dispara de novo se a conta cair abaixo
     * do limiar e passar dele outra vez.
     */
    private const THRESHOLD_PERCENT = 90;

    public function handle(HostingAccountProvisioningService $provisioning): int
    {
        $accounts = HostingAccount::with('plan', 'client')->where('status', 'active')->get();
        $count = 0;

        foreach ($accounts as $account) {
            try {
                $provisioning->refreshDiskUsage($account);
                $count++;
                $this->checkQuotaThreshold($account->fresh());
            } catch (Throwable $e) {
                $this->warn("Falha ao atualizar uso de disco da conta {$account->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$count} conta(s) atualizada(s).");

        return self::SUCCESS;
    }

    private function checkQuotaThreshold(HostingAccount $account): void
    {
        $quotaMb = $account->plan->disk_quota_mb;

        if (! $quotaMb || ! $account->disk_usage_mb) {
            return;
        }

        $percent = (int) floor(($account->disk_usage_mb / $quotaMb) * 100);

        if ($percent < self::THRESHOLD_PERCENT) {
            if ($account->disk_alert_sent_at) {
                $account->update(['disk_alert_sent_at' => null]);
            }

            return;
        }

        if ($account->disk_alert_sent_at) {
            return;
        }

        $recipients = User::where('type', 'admin')->get();

        if ($account->client) {
            $recipients->push($account->client);
        }

        Notification::send($recipients, new DiskQuotaWarningNotification($account, $percent));

        $account->update(['disk_alert_sent_at' => now()]);
    }
}
