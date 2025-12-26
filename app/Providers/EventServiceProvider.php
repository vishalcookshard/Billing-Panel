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
        InvoicePaid::class => [
            HandleInvoicePaid::class,
        ],
        InvoiceOverdue::class => [
            HandleInvoiceOverdue::class,
        ],
        InvoiceGraceWarning::class => [
            HandleInvoiceGraceWarning::class,
        ],
        InvoiceCancelled::class => [
            HandleInvoiceCancelled::class,
        ],
        InvoiceExpired::class => [
            HandleInvoiceExpired::class,
        ],
    ];

    public function boot()
    {
        parent::boot();

        // Listen for queue failures and notify admins
        $this->app['events']->listen(\Illuminate\Queue\Events\JobFailed::class, \App\Listeners\NotifyAdminsOnJobFailed::class);
    }
}
