<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Events\InvoicePaid;
use App\Events\InvoiceOverdue;
use App\Events\InvoiceCancelled;
use App\Events\InvoiceExpired;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    public function updated(Invoice $invoice)
    {
        // detect status changes
        $original = $invoice->getOriginal('status');
        $current = $invoice->status;

        if ($original === $current) {
            return;
        }

        Log::info('InvoiceObserver status changed', ['invoice_id' => $invoice->id, 'from' => $original, 'to' => $current]);

        switch ($current) {
            case Invoice::STATUS_PAID:
                InvoicePaid::dispatch($invoice);
                break;
            case Invoice::STATUS_WARNED:
                InvoiceGraceWarning::dispatch($invoice);
                break;
            case Invoice::STATUS_GRACE:
                // still in grace period; may choose to notify
                InvoiceGraceWarning::dispatch($invoice);
                break;
            case Invoice::STATUS_SUSPENDED:
                InvoiceOverdue::dispatch($invoice);
                break;
            case Invoice::STATUS_TERMINATED:
                InvoiceExpired::dispatch($invoice);
                break;
        }

        // update last_status_at without firing another observer
        $invoice->last_status_at = now();
        $invoice->saveQuietly();
    }
}
