<?php

namespace App\Services\Billing;

use App\Models\Invoice;

class TaxService
{
    /**
     * Calculate tax amount for an invoice based on invoice currency and user region.
     * This is an abstraction point to plug in country rules or external tax providers.
     * Returns array with 'tax' and 'breakdown'.
     */
    public function calculate(Invoice $invoice, array $opts = []): array
    {
        // Simplest default: no tax
        return ['tax' => 0.0, 'breakdown' => []];
    }
}
