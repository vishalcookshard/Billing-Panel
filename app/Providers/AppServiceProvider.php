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

        // Sentry integration (prod only)
        if (config('sentry.dsn') && app()->environment('production')) {
            if (class_exists('Sentry\Laravel\ServiceProvider')) {
                \Sentry\configureScope(function ($scope) {
                    if (auth()->check()) {
                        $scope->setUser([
                            'id' => auth()->id(),
                            'email' => auth()->user()->email,
                        ]);
                    }
                    // Add context for invoice/order if available in request
                    $request = request();
                    if ($request && $request->route()) {
                        $routeParams = $request->route()->parameters();
                        foreach (['invoice', 'order'] as $key) {
                            if (isset($routeParams[$key]) && is_object($routeParams[$key])) {
                                $scope->setContext($key, [
                                    'id' => $routeParams[$key]->id ?? null,
                                ]);
                            }
                        }
                    }
                });
            }
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

        // Register auth rate limiters for throttle middleware
        if (class_exists(\Illuminate\Support\Facades\RateLimiter::class)) {
            \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
                $max = (int) config('rate-limiting.auth.login.max_attempts', 5);
                $decay = (int) config('rate-limiting.auth.login.decay_minutes', 15);
                return \Illuminate\Cache\RateLimiting\Limit::perMinutes($decay, $max)->by(optional($request->input('email')) ?: $request->ip());
            });

            \Illuminate\Support\Facades\RateLimiter::for('register', function (\Illuminate\Http\Request $request) {
                $max = (int) config('rate-limiting.auth.register.max_attempts', 3);
                $decay = (int) config('rate-limiting.auth.register.decay_minutes', 60);
                return \Illuminate\Cache\RateLimiting\Limit::perMinutes($decay, $max)->by($request->ip());
            });
        }
    }
}
