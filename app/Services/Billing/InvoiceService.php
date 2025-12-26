<?php

namespace App\Services\Billing;

use App\Jobs\ProvisionJob;
use App\Models\Invoice;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Mark invoice as paid in a transaction-safe, idempotent way.
     * Returns true if marked now, false if already processed.
     */
    public function markPaid(Invoice $invoice, array $paymentData = [], ?string $idempotencyKey = null): bool
    {
        // Use DB transaction for atomicity
        return DB::transaction(function () use ($invoice, $paymentData, $idempotencyKey) {
            // Reload for latest state inside transaction
            $inv = Invoice::lockForUpdate()->find($invoice->id);

            // Idempotency: if idempotency key was processed, skip
            if ($idempotencyKey && $inv->idempotency_key && $inv->idempotency_key === $idempotencyKey) {
                Log::info('Invoice payment webhook duplicate, skipping', ['invoice_id' => $inv->id]);
                return false;
            }

            if ($inv->isPaid()) {
                // Already paid — ensure idempotency key stored
                if ($idempotencyKey && !$inv->idempotency_key) {
                    $inv->idempotency_key = $idempotencyKey;
                    $inv->save();
                }
                return false;
            }

            $inv->status = Invoice::STATUS_PAID;
            $inv->paid_at = now();
            if ($idempotencyKey) {
                $inv->idempotency_key = $idempotencyKey;
            }
            $inv->save();

            // Create an audit record
            Audit::create(["invoice_id" => $inv->id, "event" => "paid", "meta" => json_encode($paymentData)]);

            // Dispatch provisioning job (queued)
            ProvisionJob::dispatch($inv)->onQueue('billing');

            Log::info('InvoiceService marked invoice paid', ['invoice_id' => $inv->id]);

            return true;
        });
    }
}
