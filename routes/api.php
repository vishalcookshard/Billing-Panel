<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use App\Http\Controllers\Api\BillingController;

// Payment webhooks (throttled)
Route::post('webhooks/payment/{plugin}', [PaymentWebhookController::class, 'handle'])->middleware('throttle:webhooks');

// Healthcheck
Route::get('health', function () {
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

    return response()->json(['app' => 'ok', 'db' => $db, 'redis' => $redis]);
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
