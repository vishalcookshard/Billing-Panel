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
        // Require signature header for all webhooks
        $signature = $request->header('X-Webhook-Signature');
        if (!$signature || !$this->paymentService->verifyWebhookSignature($pluginKey, $request->getContent(), $signature)) {
            \App\Models\Audit::log(null, 'webhook.invalid_signature', [
                'plugin' => $pluginKey,
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Invalid webhook signature'], 401);
        }

        $result = $this->paymentService->handleWebhook($pluginKey, $request);

        \App\Models\Audit::log(null, 'webhook.received', [
            'plugin' => $pluginKey,
            'status' => $result['status'] ?? null,
            'ip' => $request->ip(),
        ]);

        if ($result['status'] === 'ok') {
            return response()->json(['status' => 'ok', 'event_id' => $result['event_id'] ?? null]);
        }

        if ($result['status'] === 'ignored') {
            return response()->json(['status' => 'ignored', 'result' => $result['result'] ?? null], 200);
        }

        return response()->json(['status' => 'error', 'message' => $result['message'] ?? null], 400);
    }
}
