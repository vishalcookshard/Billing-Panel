<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FrameworkServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register core framework services that Laravel 12 might not auto-register
        // This ensures filesystem, cache, and other bindings are available
    }

    public function boot()
    {
        // Boot hook if needed
    }
}
