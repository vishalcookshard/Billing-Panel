<?php

namespace App\Services\Billing;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Issue a credit note and optionally apply to invoice or user wallet.
     */
    public function issueCredit(Invoice $invoice = null, array $data = []): CreditNote
    {
        return DB::transaction(function () use ($invoice, $data) {
            $cn = CreditNote::create([
                'invoice_id' => $invoice?->id,
                'user_id' => $data['user_id'] ?? ($invoice?->user_id ?? null),
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? ($invoice?->currency ?? 'USD'),
                'reason' => $data['reason'] ?? null,
                'status' => 'issued',
            ]);

            Audit::log($invoice?->id ?? null, 'credit_issued', ['credit_note_id' => $cn->id, 'amount' => $cn->amount, 'actor_id' => auth()->id() ?? null]);

            return $cn;
        });
    }

    public function applyCreditToInvoice(CreditNote $credit, Invoice $invoice)
    {
        return DB::transaction(function () use ($credit, $invoice) {
            if ($credit->status !== 'issued') {
                throw new \RuntimeException('Credit not in issued state');
            }

            if ($invoice->isPaid()) {
                throw new \RuntimeException('Cannot apply credit to paid invoice');
            }

            $applyAmount = min($credit->amount, $invoice->amount);
            $invoice->amount -= $applyAmount;
            $invoice->save();

            $credit->amount -= $applyAmount;
            if ($credit->amount <= 0) {
                $credit->status = 'applied';
            }
            $credit->save();

            Audit::log($invoice->id, 'credit_applied', ['credit_note_id' => $credit->id, 'applied' => $applyAmount]);

            return ['applied' => $applyAmount, 'remaining_credit' => $credit->amount];
        });
    }
}
