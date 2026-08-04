<?php

namespace App\Domain\Support\Policies;

use App\Domain\Support\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->user_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->user_id === $user->id;
    }

    /**
     * Só admin reabre — o cliente que quer continuar depois de fechado
     * abre um chamado novo (mesma decisão documentada no plano: evita
     * uma máquina de estados mais complicada).
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }
}
