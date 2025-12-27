<?php

namespace App\Listeners;


use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotifyAdminsOnJobFailed
{
    public function handle(JobFailed $event)
    {
        $jobName = method_exists($event->job, 'getName') ? $event->job->getName() : (method_exists($event->job, 'resolveName') ? $event->job->resolveName() : 'unknown');
        $exception = $event->exception->getMessage();

        Log::error('Job Failed', [
            'job' => $jobName,
            'exception' => $exception,
            'connection' => $event->connectionName,
        ]);

        // Discord notification
        if (config('notifications.channels.discord.enabled')) {
            $this->notifyDiscord($jobName, $exception);
        }

        // Email notification
        if (config('notifications.channels.email.enabled')) {
            $this->notifyEmail($jobName, $exception);
        }

        // Slack notification
        if (config('notifications.channels.slack.enabled')) {
            $this->notifySlack($jobName, $exception);
        }
    }

    private function notifyDiscord($jobName, $exception)
    {
        $webhookUrl = config('notifications.channels.discord.webhook_url');
        if (empty($webhookUrl)) return;

        Http::post($webhookUrl, [
            'content' => "⚠️ **Job Failed**: `{$jobName}`\n```{$exception}```"
        ]);
    }

    private function notifyEmail($jobName, $exception)
    {
        $recipients = config('notifications.channels.email.recipients');
        foreach ($recipients as $recipient) {
            Mail::raw(
                "Job Failed: {$jobName}\n\nException: {$exception}",
                function ($message) use ($recipient, $jobName) {
                    $message->to($recipient)
                        ->subject("Job Failed: {$jobName}");
                }
            );
        }
    }

    private function notifySlack($jobName, $exception)
    {
        $webhookUrl = config('notifications.channels.slack.webhook_url');
        if (empty($webhookUrl)) return;

        Http::post($webhookUrl, [
            'text' => "⚠️ Job Failed: `{$jobName}`\n```{$exception}```"
        ]);
    }
}
