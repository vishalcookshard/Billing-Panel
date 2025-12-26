<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_markPaid_creates_payment_and_is_idempotent()
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID, 'amount' => 50.00]);

        $service = app(InvoiceService::class);

        $data = ['id' => 'ch_12345', 'amount' => 50.00, 'currency' => 'USD'];

        $this->assertTrue($service->markPaid($invoice, $data, 'idem-123'));

        $invoice->refresh();
        $this->assertTrue($invoice->isPaid());

        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'gateway_id' => 'ch_12345']);

        // Duplicate webhook should not create another payment
        $this->assertFalse($service->markPaid($invoice, $data, 'idem-123'));
        $this->assertEquals(1, Payment::where('invoice_id', $invoice->id)->count());
    }
}
