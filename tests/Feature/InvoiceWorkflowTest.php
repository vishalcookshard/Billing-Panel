<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\Audit;

class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_promo_reduces_invoice_amount()
    {
        $user = User::factory()->create();
        $inv = Invoice::factory()->create(['user_id' => $user->id, 'amount' => 100.00]);

        $promo = PromoCode::create(['code' => 'TEST10', 'type' => 'percentage', 'value' => 10, 'expires_at' => now()->addDay()]);

        $result = (new \App\Services\Billing\PromoService())->applyPromoToInvoice('TEST10', $inv);

        $this->assertTrue($result['success']);
        $this->assertEquals(10.0, $result['discount']);

        $inv->refresh();
        $this->assertEquals(90.00, (float)$inv->amount);
    }

    public function test_issue_credit_creates_audit_record()
    {
        $user = User::factory()->create();
        $inv = Invoice::factory()->create(['user_id' => $user->id, 'amount' => 50.00]);

        $cn = (new \App\Services\Billing\CreditService())->issueCredit($inv, ['amount' => 10.00, 'currency' => 'USD', 'user_id' => $user->id]);

        $this->assertDatabaseHas('credit_notes', ['id' => $cn->id]);
        $this->assertDatabaseHas('audits', ['event' => 'credit_issued']);
    }
}
