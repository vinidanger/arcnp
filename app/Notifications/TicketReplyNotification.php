<?php

namespace App\Notifications;

use App\Domain\Support\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketReplyNotification extends Notification
{
    use Queueable;

    public function __construct(private Ticket $ticket, private string $body)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $routeName = $notifiable instanceof User && $notifiable->isAdmin()
            ? 'admin.tickets.show'
            : 'client.tickets.show';

        return (new MailMessage)
            ->subject("Nova resposta no chamado — {$this->ticket->subject}")
            ->line("Chamado: {$this->ticket->subject}")
            ->line(Str::limit($this->body, 200))
            ->action('Ver chamado', route($routeName, $this->ticket));
    }
}
