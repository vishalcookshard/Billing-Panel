<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Show checkout page for a plan
     */
    public function show(Request $request, $planId)
    {
        $plan = Plan::findOrFail($planId);

        if (!$plan->is_active) {
            abort(404);
        }

        $billingCycle = $request->query('cycle', 'monthly');
        if (!in_array($billingCycle, ['monthly', 'yearly', 'lifetime'])) {
            $billingCycle = 'monthly';
        }

        $price = $plan->getPrice($billingCycle);

        return view('checkout.show', [
            'plan' => $plan,
            'billingCycle' => $billingCycle,
            'price' => $price,
        ]);
    }

    /**
     * Process the checkout
     */
    public function process(Request $request, $planId)
    {
        $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'payment_method' => 'required|string|in:card,paypal,crypto',
            'gateway' => 'required|string|exists:gateways,key',
            'promo_code' => 'nullable|string|exists:promo_codes,code',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login')->with('redirect', route('checkout.show', $planId));
        }

        $plan = Plan::findOrFail($planId);

        if (!$plan->is_active) {
            abort(404);
        }

        $billingCycle = $request->input('billing_cycle');
        $price = $plan->getPrice($billingCycle);

        // Create an order and dispatch provisioning via service
        $orderService = app(\App\Services\Billing\OrderService::class);
        $order = $orderService->createOrderAndDispatch($user, $plan, $billingCycle, $price);

        return redirect()->route('dashboard.orders')
            ->with('success', "Order placed successfully! Provisioning has been queued for {$plan->name}.");
    }
}
