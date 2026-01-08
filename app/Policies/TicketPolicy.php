<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ticket;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket)
    {
        return $user->id === $ticket->user_id || $user->isAdmin();
    }

    public function reply(User $user, Ticket $ticket)
    {
        return $user->id === $ticket->user_id || $user->isAdmin();
    }

    public function close(User $user, Ticket $ticket)
    {
        return $user->id === $ticket->user_id || $user->isAdmin();
    }
}
