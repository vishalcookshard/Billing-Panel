<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketReplyStaff extends Mailable
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
        return $this->subject('Staff Replied to Ticket')
            ->view('emails.ticket-reply-staff')
            ->with([
                'user' => $this->user,
                'ticket' => $this->ticket,
            ]);
    }
}
