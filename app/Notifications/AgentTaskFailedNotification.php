<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Cobre tarefas que rodam em background sem ninguém olhando na hora
 * (renovação de SSL, backup agendado) — diferente de falha síncrona
 * (ex: emitir SSL na hora), que já aparece na tela pra quem clicou.
 */
class AgentTaskFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $taskLabel,
        private string $context,
        private string $error,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Falha: {$this->taskLabel}")
            ->line("{$this->taskLabel} falhou.")
            ->line("Contexto: {$this->context}")
            ->line("Erro: {$this->error}");
    }
}
