<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;

class InvoiceStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_invoice_is_immutable_and_transitions_enforced()
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID]);

        // valid transition
        $this->assertTrue($invoice->transitionTo(Invoice::STATUS_PAID));

        // cannot change paid to unpaid
        $this->expectException(\RuntimeException::class);
        $invoice->status = Invoice::STATUS_UNPAID;
        $invoice->save();
    }
}
