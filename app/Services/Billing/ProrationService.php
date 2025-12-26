<?php

namespace App\Services\Billing;

use App\Models\Order;
use Carbon\Carbon;

class ProrationService
{
    /**
     * Calculate prorated amount when switching plans mid-cycle.
     * Returns positive number to charge (upgrade) or negative to credit (downgrade).
     */
    public function calculate(Order $order, float $newAmount): float
    {
        if (!$order->renewal_date) return $newAmount - $order->amount;

        $now = Carbon::now();
        $end = Carbon::parse($order->renewal_date);
        $start = $order->created_at ?? $now->copy()->subDays(30);
        $totalDays = max(1, $end->diffInDays($start));
        $remainingDays = max(0, $end->diffInDays($now));

        $currentDaily = $order->amount / $totalDays;
        $newDaily = $newAmount / $totalDays;

        $credit = $currentDaily * $remainingDays;
        $charge = $newDaily * $remainingDays;

        // Net amount: charge - credit
        return round($charge - $credit, 2);
    }
}
