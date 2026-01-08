<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentOverdue extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $invoice;

    public function __construct($user, $invoice)
    {
        $this->user = $user;
        $this->invoice = $invoice;
    }

    public function build()
    {
        return $this->subject('Payment Overdue')
            ->view('emails.payment-overdue')
            ->with([
                'user' => $this->user,
                'invoice' => $this->invoice,
            ]);
    }
}
