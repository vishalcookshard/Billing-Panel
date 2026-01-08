<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\PaymentWebhookController;
use App\Http\Controllers\Api\BillingController;

// Payment webhooks (strictly rate-limited)
Route::post('webhooks/payment/{plugin}', [PaymentWebhookController::class, 'handle'])
    ->middleware(['throttle:webhooks', 'signed']);

// Healthcheck
Route::get('health', function (\Illuminate\Http\Request $request) {
    // Restrict detailed health info to authorized probes
    $token = $request->header('X-Monitoring-Token');

    $authorized = false;
    if ($token && $token === config('app.monitoring_token')) {
        $authorized = true;
    }

    if ($request->user() && ($request->user()->is_admin ?? false || ($request->user()->role ?? '') === 'admin')) {
        $authorized = true;
    }

    if (!$authorized) {
        // Minimal response for unauthenticated checks
        return response()->json(['status' => 'ok']);
    }

    // Detailed checks - only for authorized probes
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

    // Queue check
    try {
        \Illuminate\Support\Facades\Redis::set('system:queue_test', time());
        $queue_ok = true;
    } catch (\Throwable $e) {
        $queue_ok = false;
    }

    return response()->json(['app' => 'ok', 'db' => $db, 'redis' => $redis, 'scheduler' => $scheduler_ok, 'queue' => $queue_ok]);
})->middleware('throttle:health');

// Billing API: requires authentication and strict rate limiting
Route::middleware(['auth', 'throttle:billing'])->group(function () {
    Route::post('invoices/{invoice}/apply-promo', [BillingController::class, 'applyPromo']);
    Route::get('users/{user}/wallet', [BillingController::class, 'wallet']);
});

// Admin billing actions (strict rate limiting)
Route::middleware(['auth', 'permission:manage-settings', 'throttle:admin'])->prefix('admin')->group(function () {
    Route::post('users/{user}/wallet/credit', [BillingController::class, 'creditWallet']);
    Route::post('credit-notes', [BillingController::class, 'issueCredit']);
    Route::post('invoices/{invoice}/pdf', [BillingController::class, 'invoicePdf']);
});
