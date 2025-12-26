<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Notifications\AdminJobFailedNotification;
use App\Models\User;

class NotifyAdminsOnJobFailed implements ShouldQueue
{
    public function handle(JobFailed $event)
    {
        try {
            Log::warning('Job failed, notifying admins', ['connection' => $event->connectionName, 'job' => $event->job->getName()]);

            $admins = User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new AdminJobFailedNotification($event));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to notify admins about job failure: ' . $e->getMessage());
        }
    }
}
