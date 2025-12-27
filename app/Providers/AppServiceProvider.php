<?php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Invoice::observe(InvoiceObserver::class);

        // Skip all runtime checks in console (artisan)
        if (app()->runningInConsole()) {
            return;
        }

        $this->app->booted(function () {
            // Rate limiters
            RateLimiter::for('webhooks', function (Request $request) {
                $key = $request->ip() ?: $request->header('X-Forwarded-For', 'global');
                return Limit::perMinute(30)->by($key);
            });

            RateLimiter::for('health', function (Request $request) {
                $key = $request->header('X-Monitoring-Token') ?: $request->ip();
                return Limit::perMinute(10)->by($key);
            });

            RateLimiter::for('login', function (Request $request) {
                $key = $request->ip() ?: 'global';
                return Limit::perMinute(5)->by($key)->response(function () {
                    return response('Too many login attempts. Please try later.', 429);
                });
            });

            // Production checks
            if (config('app.env') === 'production') {
                $driver = config('queue.default');
                if ($driver === 'sync') {
                    \Log::warning('Queue driver is set to sync in production.');
                }

                // Redis check (non-fatal)
                if ($driver === 'redis') {
                    try {
                        if (\Illuminate\Support\Facades\Redis::ping() !== 'PONG') {
                            \Log::error('Redis unavailable for queue.');
                        }
                    } catch (\Throwable $e) {
                        \Log::error('Queue connectivity check failed: ' . $e->getMessage());
                    }
                }
            }
        });
    }
}
}
