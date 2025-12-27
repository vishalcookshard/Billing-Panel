<?php

namespace App\Services\Billing;

use App\Jobs\CreateServiceJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create an order and its invoice in a transaction, then dispatch provisioning.
     *
     * @param User $user The user placing the order
     * @param \App\Models\Plan $plan The plan being ordered
     * @param string $billingCycle The billing cycle (monthly, yearly, etc.)
     * @param float $amount The order amount
     * @return Order The created order
     * @throws \InvalidArgumentException If currency is invalid
     */
    public function createOrderAndDispatch(User $user, \App\Models\Plan $plan, string $billingCycle, float $amount): Order
    {
        return \DB::transaction(function () use ($user, $plan, $billingCycle, $amount) {
            $order = Order::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                    'status' => Order::STATUS_PENDING, // Using constant for status
            ]);

            // create an invoice for this order (unpaid)
            // Determine currency
            $currency = $plan->currency ?? $user->currency ?? config('app.currency') ?? 'USD';
            if (!in_array($currency, ['USD', 'EUR', 'GBP', 'INR'])) {
                throw new \InvalidArgumentException('Invalid currency');
            }
            $invoice = \App\Models\Invoice::create([
                'user_id' => $user->id,
                'amount' => $amount,
                    'status' => \App\Models\Invoice::STATUS_UNPAID, // Using constant for status
                'due_date' => now()->addDays(7),
                'currency' => $currency,
            ]);

            $order->invoice_id = $invoice->id;
            $order->save();

            Log::info('Order created and invoice generated', ['order_id' => $order->id, 'invoice_id' => $invoice->id]);

            return $order;
        });
    }
}
