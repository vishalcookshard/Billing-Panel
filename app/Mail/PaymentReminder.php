<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $invoice;
    public $daysLeft;

    public function __construct($user, $invoice, $daysLeft)
    {
        $this->user = $user;
        $this->invoice = $invoice;
        $this->daysLeft = $daysLeft;
    }

    public function build()
    {
        $subject = $this->daysLeft == 3 ? 'Payment Reminder: 3 Days Left' : 'Payment Reminder: 1 Day Left';
        return $this->subject($subject)
            ->view('emails.payment-reminder')
            ->with([
                'user' => $this->user,
                'invoice' => $this->invoice,
                'daysLeft' => $this->daysLeft,
            ]);
    }
}
