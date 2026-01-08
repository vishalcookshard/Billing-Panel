<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $bodyText;

    public function __construct($subjectText, $bodyText)
    {
        $this->subjectText = $subjectText;
        $this->bodyText = $bodyText;
    }

    public function build()
    {
        return $this->subject($this->subjectText)
            ->view('emails.generic')
            ->with(['bodyText' => $this->bodyText]);
    }
}
