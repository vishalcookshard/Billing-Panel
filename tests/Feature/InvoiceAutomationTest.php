<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Models\Invoice;
use App\Events\InvoicePaid;

class InvoiceAutomationTest extends TestCase
{
    public function test_invoice_paid_triggers_event()
    {
        Event::fake();

        $invoice = Invoice::factory()->create(['status' => 'pending']);

        $service = app(\App\Services\Billing\InvoiceService::class);
        $service->markPaid($invoice, [], 'test-evt');

        Event::assertDispatched(InvoicePaid::class);
    }
}
