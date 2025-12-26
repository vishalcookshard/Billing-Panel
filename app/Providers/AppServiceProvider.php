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
        });
    }
}
