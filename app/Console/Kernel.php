<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ScanInvoices;

class Kernel extends ConsoleKernel
{
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
    {
        $schedule->command('invoices:scan')->daily();

        // Heartbeat used by health endpoint to confirm scheduler is running
        $schedule->command('system:heartbeat')->everyMinute()->runInBackground()->onFailure(function () {
            // Notify admins on failure and log at critical level
            \Log::critical('system:heartbeat scheduled task failed to run');
            // Attempt to notify admins via notification channel (synchronous)
            try {
                $admins = \App\Models\User::where('is_admin', true)->get();
                foreach ($admins as $admin) {
                    $admin->notifyNow(new \App\Notifications\AdminJobFailedNotification((object)['connectionName' => 'scheduler', 'job' => (object)['name' => 'system:heartbeat'], 'exception' => new \Exception('schedule_failure')]));
                }
            } catch (\Throwable $e) {
                \Log::error('Failed to notify admins about scheduler failure: ' . $e->getMessage());
            }
        });

        // Prune old failed jobs daily to keep failed_jobs table manageable
        $schedule->command('queue:prune-failed --hours=168')->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
