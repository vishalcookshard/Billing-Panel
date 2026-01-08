<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\TicketReply;

class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reply;

    public function __construct(TicketReply $reply)
    {
        $this->reply = $reply;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Ticket Reply')
            ->line('A reply has been posted to your ticket.')
            ->action('View Ticket', url('/dashboard/tickets/' . $this->reply->ticket_id));
    }
}
