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

            // Create or ensure payment record (idempotent)
            if (!empty($paymentData)) {
                $existing = null;
                if (!empty($paymentData['id'])) {
                    $existing = \App\Models\Payment::where('gateway_id', $paymentData['id'])->orWhere('idempotency_key', $idempotencyKey)->first();
                }

                if (!$existing) {
                    \App\Models\Payment::create([
                        'invoice_id' => $inv->id,
                        'gateway' => $paymentData['object_type'] ?? ($paymentData['gateway'] ?? 'gateway'),
                        'gateway_id' => $paymentData['id'] ?? null,
                        'idempotency_key' => $idempotencyKey,
                        'amount' => $paymentData['amount'] ?? $inv->amount,
                        'currency' => $paymentData['currency'] ?? $inv->currency,
                        'meta' => $paymentData,
                    ]);
                }
            }

            // Create an audit record
            Audit::create(["invoice_id" => $inv->id, "event" => "paid", "meta" => json_encode($paymentData)]);

            // Dispatch provisioning job (queued)
            ProvisionJob::dispatch($inv)->onQueue('billing');

            Log::info('InvoiceService marked invoice paid', ['invoice_id' => $inv->id]);

            return true;
        });
    }
}
