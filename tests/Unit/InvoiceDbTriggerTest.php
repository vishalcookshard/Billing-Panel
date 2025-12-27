    public function test_db_prevents_deleting_paid_invoice()
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_PAID, 'amount' => 100]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $invoice->delete();
    }
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;

class InvoiceDbTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_prevents_modifying_paid_invoice()
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID, 'amount' => 100]);

        $invoice->status = Invoice::STATUS_PAID;
        $invoice->paid_at = now();
        $invoice->save();

        // Attempt to change amount should throw a database exception
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('invoices')->where('id', $invoice->id)->update(['amount' => 200]);
    }
}
