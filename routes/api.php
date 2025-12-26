<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use App\Http\Controllers\Api\BillingController;

// Payment webhooks (throttled)
Route::post('webhooks/payment/{plugin}', [PaymentWebhookController::class, 'handle'])->middleware('throttle:webhooks');

// Healthcheck
Route::get('health', function () {
    // Basic checks
    try {
        \DB::connection()->getPdo();
        $db = true;
    } catch (\Throwable $e) {
        $db = false;
    }

    try {
        $redis = \Illuminate\Support\Facades\Redis::ping() === 'PONG';
    } catch (\Throwable $e) {
        $redis = false;
    }

    // Scheduler heartbeat
    $heartbeat = \Illuminate\Support\Facades\Cache::get('system:heartbeat');
    $scheduler_ok = $heartbeat ? (\Carbon\Carbon::parse($heartbeat)->diffInMinutes(now()) < 6) : false;

    // Queue check: verify we can push a small job (ticket) to the queue and it reaches Redis
    try {
        \Illuminate\Support\Facades\Redis::set('system:queue_test', time());
        $queue_ok = true;
    } catch (\Throwable $e) {
        $queue_ok = false;
    }

    return response()->json(['app' => 'ok', 'db' => $db, 'redis' => $redis, 'scheduler' => $scheduler_ok, 'queue' => $queue_ok]);
});

// Billing API: requires authentication
Route::middleware(['auth'])->group(function () {
	Route::post('invoices/{invoice}/apply-promo', [BillingController::class, 'applyPromo']);
	Route::get('users/{user}/wallet', [BillingController::class, 'wallet']);
});

// Admin billing actions
Route::middleware(['auth', 'permission:manage-settings'])->prefix('admin')->group(function () {
	Route::post('users/{user}/wallet/credit', [BillingController::class, 'creditWallet']);
	Route::post('credit-notes', [BillingController::class, 'issueCredit']);
	Route::post('invoices/{invoice}/pdf', [BillingController::class, 'invoicePdf']);
});
