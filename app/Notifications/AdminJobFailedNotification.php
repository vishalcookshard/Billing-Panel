<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Queue\SerializesModels;

class AdminJobFailedNotification extends Notification
{
    use Queueable, SerializesModels;

    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $job = method_exists($this->event->job, 'getName') ? $this->event->job->getName() : 'Unknown';
        $connection = $this->event->connectionName ?? 'unknown';
        $exception = $this->event->exception ?? null;
        $exceptionMessage = $exception ? (is_object($exception) && method_exists($exception, 'getMessage') ? $exception->getMessage() : (string)$exception) : 'n/a';

        $message = (new MailMessage)
            ->error()
            ->subject("[BillingPanel] Queue job failed: {$job}")
            ->line("A background job failed on connection {$connection}.")
            ->line("Exception: {$exceptionMessage}")
            ->line('Check logs and failed jobs: php artisan queue:failed')
            ->line('This is an automated notification.');

        return $message;
    }
}
