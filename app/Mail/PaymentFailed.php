<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
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
        return $this->subject('Payment Failed')
            ->view('emails.payment-failed')
            ->with([
                'user' => $this->user,
                'invoice' => $this->invoice,
            ]);
    }
}
