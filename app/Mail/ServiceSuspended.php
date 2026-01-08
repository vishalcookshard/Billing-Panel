<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceSuspended extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $service;

    public function __construct($user, $service)
    {
        $this->user = $user;
        $this->service = $service;
    }

    public function build()
    {
        return $this->subject('Service Suspended')
            ->view('emails.service-suspended')
            ->with([
                'user' => $this->user,
                'service' => $this->service,
            ]);
    }
}
