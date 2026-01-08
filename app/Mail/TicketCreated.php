<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $ticket;

    public function __construct($user, $ticket)
    {
        $this->user = $user;
        $this->ticket = $ticket;
    }

    public function build()
    {
        return $this->subject('Support Ticket Created')
            ->view('emails.ticket-created')
            ->with([
                'user' => $this->user,
                'ticket' => $this->ticket,
            ]);
    }
}
