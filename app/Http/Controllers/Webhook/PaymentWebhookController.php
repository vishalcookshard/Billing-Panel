<?php

namespace App\Http\Controllers\Webhook;

use Illuminate\Http\Request;
use App\Plugins\PluginManager;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected \App\Services\Billing\PaymentService $paymentService;

    public function __construct(\App\Services\Billing\PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request, $pluginKey)
    {
        $result = $this->paymentService->handleWebhook($pluginKey, $request);

        if ($result['status'] === 'ok') {
            return response()->json(['status' => 'ok', 'event_id' => $result['event_id'] ?? null]);
        }

        if ($result['status'] === 'ignored') {
            return response()->json(['status' => 'ignored', 'result' => $result['result'] ?? null], 200);
        }

        return response()->json(['status' => 'error', 'message' => $result['message'] ?? null], 400);
    }
}
