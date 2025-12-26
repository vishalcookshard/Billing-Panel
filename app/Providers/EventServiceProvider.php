<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\InvoicePaid;
use App\Events\InvoiceOverdue;
use App\Events\InvoiceGraceWarning;
use App\Events\InvoiceCancelled;
use App\Events\InvoiceExpired;
use App\Listeners\HandleInvoicePaid;
use App\Listeners\HandleInvoiceOverdue;
use App\Listeners\HandleInvoiceGraceWarning;
use App\Listeners\HandleInvoiceCancelled;
use App\Listeners\HandleInvoiceExpired;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Disabled temporarily due to Laravel 12 bootstrap issues
        // Events will still fire, but listeners won't be automatically wired
    ];

    public function boot()
    {
        // Disabled parent boot to avoid framework binding errors
        // Event listeners can be registered manually in routes or controllers if needed
    }
}
