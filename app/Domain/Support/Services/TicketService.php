<?php

namespace App\Domain\Support\Services;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Support\Models\Ticket;
use App\Domain\Support\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class TicketService
{
    public function create(User $client, string $subject, string $priority, ?HostingAccount $account, string $body): Ticket
    {
        $ticket = $client->tickets()->create([
            'hosting_account_id' => $account?->id,
            'subject' => $subject,
            'priority' => $priority,
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => $client->id,
            'body' => $body,
        ]);

        Notification::send($this->admins(), new TicketReplyNotification($ticket, $body));

        return $ticket;
    }

    public function reply(Ticket $ticket, User $author, string $body): TicketMessage
    {
        if ($ticket->status === 'closed') {
            throw new RuntimeException('Esse chamado está fechado — abra um novo se precisar continuar.');
        }

        $message = $ticket->messages()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);

        $isAuthorAdmin = $author->isAdmin();

        $ticket->update(['status' => $isAuthorAdmin ? 'answered' : 'open']);

        $recipients = $isAuthorAdmin ? [$ticket->user] : $this->admins();
        Notification::send($recipients, new TicketReplyNotification($ticket, $body));

        return $message;
    }

    public function close(Ticket $ticket): void
    {
        $ticket->update(['status' => 'closed']);
    }

    public function reopen(Ticket $ticket): void
    {
        $ticket->update(['status' => 'open']);
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function admins()
    {
        return User::where('type', 'admin')->get();
    }
}
