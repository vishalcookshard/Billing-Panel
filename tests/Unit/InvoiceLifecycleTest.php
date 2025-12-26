<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_transition_through_lifecycle()
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID, 'due_date' => now()->subDays(10)]);

        $this->assertTrue($invoice->canTransitionTo(Invoice::STATUS_WARNED));
        $invoice->transitionTo(Invoice::STATUS_WARNED);
        $this->assertEquals(Invoice::STATUS_WARNED, $invoice->fresh()->status);

        $this->assertTrue($invoice->canTransitionTo(Invoice::STATUS_GRACE));
        $invoice->transitionTo(Invoice::STATUS_GRACE);
        $this->assertEquals(Invoice::STATUS_GRACE, $invoice->fresh()->status);

        $this->assertTrue($invoice->canTransitionTo(Invoice::STATUS_SUSPENDED));
        $invoice->transitionTo(Invoice::STATUS_SUSPENDED);
        $this->assertEquals(Invoice::STATUS_SUSPENDED, $invoice->fresh()->status);

        $this->assertTrue($invoice->canTransitionTo(Invoice::STATUS_TERMINATED));
        $invoice->transitionTo(Invoice::STATUS_TERMINATED);
        $this->assertEquals(Invoice::STATUS_TERMINATED, $invoice->fresh()->status);
    }

    public function test_invalid_transition_throws()
    {
        $this->expectException(\RuntimeException::class);

        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_PAID]);
        $invoice->transitionTo(Invoice::STATUS_UNPAID);
    }
}
