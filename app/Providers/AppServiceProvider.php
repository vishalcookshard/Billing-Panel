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

        // Defer rate limiter registration until the container has finished booting
        $this->app->booted(function () {
            // Rate limit payment webhooks to mitigate replay/abuse
            RateLimiter::for('webhooks', function (Request $request) {
                $key = $request->ip() ?: $request->header('X-Forwarded-For', 'global');
                return Limit::perMinute(30)->by($key);
            });

            // Rate limiter for health checks (protected)
            RateLimiter::for('health', function (Request $request) {
                $key = $request->header('X-Monitoring-Token') ?: $request->ip();
                return Limit::perMinute(10)->by($key);
            });

            // Login attempts limiter
            RateLimiter::for('login', function (Request $request) {
                $key = $request->ip() ?: 'global';
                return Limit::perMinute(5)->by($key)->response(function () {
                    return response('Too many login attempts. Please try later.', 429);
                });
            });

            // Enforce queue system in production: do not allow 'sync' driver
            if (config('app.env') === 'production') {
                $driver = config('queue.default');
                if ($driver === 'sync') {
                    throw new \RuntimeException('Queue driver is set to sync in production. Set QUEUE_CONNECTION to a proper queue and ensure workers are running.');
                }

                // Only check Redis if not running in console (artisan)
                if ($driver === 'redis' && !app()->runningInConsole()) {
                    try {
                        if (\Illuminate\Support\Facades\Redis::ping() !== 'PONG') {
                            // Log error, do not throw
                            \Log::error('Redis unavailable for queue.');
                        }
                    } catch (\Throwable $e) {
                        // Log error, do not throw
                        \Log::error('Queue connectivity check failed: ' . $e->getMessage());
                    }
                }

                // Validate critical environment variables in production
                $required = ['APP_KEY', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'REDIS_HOST', 'QUEUE_CONNECTION'];
                $missing = [];
                foreach ($required as $k) {
                    if (empty(env($k))) $missing[] = $k;
                }

                if (!empty($missing)) {
                    throw new \RuntimeException('Missing required environment variables for production: ' . implode(', ', $missing));
                }

                // Ensure APP_KEY is strong (base64: with length >= 44)
                $appKey = env('APP_KEY');
                if (!preg_match('/^base64:[A-Za-z0-9+\/=]{43,}$/', $appKey)) {
                    throw new \RuntimeException('APP_KEY is missing or not a valid base64 key. Generate one with php artisan key:generate');
                }

                // Prevent using root DB user in production
                if (strtolower(env('DB_USERNAME', '')) === 'root') {
                    throw new \RuntimeException('DB_USERNAME is set to root in production. Use a dedicated non-root database user with minimal privileges.');
                }
            }
        });
    }
}
