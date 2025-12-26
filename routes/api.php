<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use App\Http\Controllers\Api\BillingController;

// Payment webhooks (throttled)
Route::post('webhooks/payment/{plugin}', [PaymentWebhookController::class, 'handle'])->middleware('throttle:webhooks');

// Billing API: requires authentication
Route::middleware(['auth'])->group(function () {
	Route::post('invoices/{invoice}/apply-promo', [BillingController::class, 'applyPromo']);
	Route::get('users/{user}/wallet', [BillingController::class, 'wallet']);
});

// Admin billing actions
Route::middleware(['auth', 'can:manage-settings'])->prefix('admin')->group(function () {
	Route::post('users/{user}/wallet/credit', [BillingController::class, 'creditWallet']);
	Route::post('credit-notes', [BillingController::class, 'issueCredit']);
	Route::post('invoices/{invoice}/pdf', [BillingController::class, 'invoicePdf']);
});
