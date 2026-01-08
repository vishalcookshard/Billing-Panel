<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketClosed extends Mailable
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
        return $this->subject('Ticket Closed')
            ->view('emails.ticket-closed')
            ->with([
                'user' => $this->user,
                'ticket' => $this->ticket,
            ]);
    }
}
