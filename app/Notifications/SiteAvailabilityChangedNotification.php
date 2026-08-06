<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Uma classe só, parametrizada por "isDown" — evita duplicar 2 classes
 * quase idênticas pra "caiu" e "voltou".
 */
class SiteAvailabilityChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private string $label, private bool $isDown)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isDown) {
            return (new MailMessage)
                ->subject("Fora do ar — {$this->label}")
                ->line("{$this->label} parou de responder (2 checagens seguidas sem sucesso).")
                ->line('Você vai receber outro aviso quando ele voltar ao ar.');
        }

        return (new MailMessage)
            ->subject("De volta ao ar — {$this->label}")
            ->line("{$this->label} voltou a responder normalmente.");
    }
}
