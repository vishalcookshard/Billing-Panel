<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Invoice::observe(InvoiceObserver::class);

        // Never block CLI or artisan commands
        if (app()->runningInConsole()) {
            return;
        }

        // Redis check: only log warning, never throw
        try {
            if (config('queue.default') === 'redis') {
                if (Redis::ping() !== 'PONG') {
                    Log::warning('Redis unavailable for queue.');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Redis check failed: ' . $e->getMessage());
        }
    }
}
}
