<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceCreated extends Mailable
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
        return $this->subject('Service Activated')
            ->view('emails.service-created')
            ->with([
                'user' => $this->user,
                'service' => $this->service,
            ]);
    }
}
