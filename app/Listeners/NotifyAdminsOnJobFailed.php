<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Notifications\AdminJobFailedNotification;
use App\Models\User;

class NotifyAdminsOnJobFailed
{
    public function handle(JobFailed $event)
    {
        try {
            // Detailed logging with exception information
            $payload = [];
            try {
                $payload = $event->job->payload() ?? [];
            } catch (\Throwable $e) {
                // ignore payload extraction errors
            }

            Log::error('Job failed', [
                'connection' => $event->connectionName,
                'job' => method_exists($event->job, 'getName') ? $event->job->getName() : 'unknown',
                'exception' => $event->exception->getMessage(),
                'payload' => $payload,
            ]);

            // Notify admins immediately (synchronous) to avoid missing alerts when queue workers fail
            $admins = User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                // Use notifyNow to send synchronously
                $admin->notifyNow(new AdminJobFailedNotification($event));
            }
        } catch (\Throwable $e) {
            Log::critical('Failed to notify admins about job failure: ' . $e->getMessage(), ['original_exception' => $event->exception->getMessage() ?? null]);
        }
    }
}
