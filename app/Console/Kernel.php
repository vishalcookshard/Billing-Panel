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
        $schedule->command('system:heartbeat')->everyMinute()->runInBackground();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}
