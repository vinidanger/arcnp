<?php

namespace App\Notifications;

use App\Domain\Hosting\Models\HostingAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DiskQuotaWarningNotification extends Notification
{
    use Queueable;

    public function __construct(private HostingAccount $account, private int $percent)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $usedMb = $this->account->disk_usage_mb;
        $quotaMb = $this->account->plan->disk_quota_mb;

        return (new MailMessage)
            ->subject("Uso de disco alto — {$this->account->primary_domain}")
            ->line("A conta {$this->account->primary_domain} está usando {$this->percent}% da cota de disco do plano.")
            ->line("Uso atual: {$usedMb} MB de {$quotaMb} MB.")
            ->line('Considere fazer limpeza ou migrar pra um plano com mais espaço.');
    }
}
