<?php

namespace App\Services\Billing;

use App\Models\PromoCode;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromoService
{
    public function applyPromoToInvoice(string $code, Invoice $invoice): array
    {
        $promo = PromoCode::where('code', $code)->first();
        if (!$promo) {
            return ['success' => false, 'message' => 'promo_not_found'];
        }

        if (!$promo->isValid()) {
            return ['success' => false, 'message' => 'promo_invalid'];
        }

        // Must apply before invoice is paid
        if ($invoice->isPaid()) {
            return ['success' => false, 'message' => 'invoice_already_paid'];
        }

        return DB::transaction(function () use ($promo, $invoice) {
            // Calculate discount
            if ($promo->type === 'percentage') {
                $discount = ($invoice->amount * ($promo->value / 100));
            } else {
                $discount = (float)$promo->value;
            }

            $invoice->amount = max(0, $invoice->amount - $discount);
            $invoice->save();

            $promo->times_used += 1;
            $promo->save();

            Log::info('Applied promo to invoice', ['promo' => $promo->code, 'invoice_id' => $invoice->id, 'discount' => $discount]);

            return ['success' => true, 'discount' => $discount, 'invoice_id' => $invoice->id];
        });
    }
}
