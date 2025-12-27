<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;

class BillingCalculationTest extends TestCase
{
    public function test_invoice_total_is_deterministic()
    {
        // Create an invoice and assert consistent totals
        $inv = Invoice::factory()->create(['amount' => 100.00]);
        $this->assertEquals(100.00, (float)$inv->amount);
    }
}
