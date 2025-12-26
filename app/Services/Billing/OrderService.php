<?php

namespace App\Services\Billing;

use App\Jobs\CreateServiceJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function createOrderAndDispatch(User $user, $plan, string $billingCycle, float $amount): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'status' => Order::STATUS_PENDING,
        ]);

        // create an invoice for this order (unpaid)
        $invoice = \App\Models\Invoice::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => \App\Models\Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
            'currency' => 'USD',
        ]);

        $order->invoice_id = $invoice->id;
        $order->save();

        Log::info('Order created and invoice generated', ['order_id' => $order->id, 'invoice_id' => $invoice->id]);

        return $order;
    }
}
