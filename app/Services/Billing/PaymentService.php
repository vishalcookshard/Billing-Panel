<?php

namespace App\Services\Billing;

use App\Models\WebhookEvent;
use App\Plugins\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected PluginManager $manager;
    protected InvoiceService $invoiceService;

    public function __construct(PluginManager $manager, InvoiceService $invoiceService)
    {
        $this->manager = $manager;
        $this->manager->discover();
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle a payment webhook in an idempotent, transactional manner.
     */
    public function handleWebhook(string $pluginKey, Request $request): array
    {
        $meta = $this->manager->get($pluginKey);
        if (!$meta) {
            Log::warning('Webhook for unknown plugin', ['plugin' => $pluginKey]);
            return ['status' => 'error', 'message' => 'unknown_plugin'];
        }

        $class = $meta['class'] ?? null;
        if (!$class || !class_exists($class)) {
            Log::warning('Webhook plugin class missing', ['plugin' => $pluginKey]);
            return ['status' => 'error', 'message' => 'plugin_missing'];
        }

        $plugin = app($class);

        // Optional signature verification
        if (method_exists($plugin, 'verifyWebhookSignature')) {
            if (!$plugin->verifyWebhookSignature($request)) {
                Log::warning('Webhook signature verification failed', ['plugin' => $pluginKey]);
                return ['status' => 'error', 'message' => 'signature_invalid'];
            }
        }

        $payload = $request->getContent();
        $eventId = null;
        try {
            $data = json_decode($payload, true) ?? [];
            $eventId = $data['id'] ?? null; // common for Stripe
            if (!$eventId) {
                // fallback: use a hash of payload
                $eventId = 'payload_' . sha1($payload);
            }
        } catch (\Throwable $e) {
            $eventId = 'payload_' . sha1($payload);
            $data = [];
        }

        // Ensure idempotency: store webhook event and reject duplicates
        try {
            return DB::transaction(function () use ($eventId, $pluginKey, $payload, $plugin, $request) {
                $existing = WebhookEvent::where('event_id', $eventId)->first();
                if ($existing) {
                    Log::info('Webhook event already processed', ['event_id' => $eventId]);
                    return ['status' => 'ok', 'message' => 'duplicate'];
                }

                $we = WebhookEvent::create([
                    'event_id' => $eventId,
                    'plugin' => $pluginKey,
                    'payload' => $payload,
                    'processed' => false,
                ]);

                // Let plugin handle webhook in a safe way
                try {
                    $result = $plugin->handleWebhook($request);
                } catch (\Throwable $e) {
                    Log::error('Webhook handler error', ['plugin' => $pluginKey, 'error' => $e->getMessage(), 'event_id' => $eventId]);
                    // mark as failed (but keep record)
                    $we->update(['processed' => false, 'result' => json_encode(['error' => $e->getMessage()])]);
                    throw $e;
                }

                if (!empty($result['handled'])) {
                    $we->update(['processed' => true, 'processed_at' => now(), 'result' => json_encode($result)]);
                    return ['status' => 'ok', 'handled' => true, 'event_id' => $eventId];
                }

                // Not handled
                $we->update(['result' => json_encode($result)]);
                return ['status' => 'ignored', 'result' => $result];
            });
        } catch (\Throwable $e) {
            Log::error('Webhook processing transaction failed', ['error' => $e->getMessage(), 'event_id' => $eventId]);
            return ['status' => 'error', 'message' => 'processing_error'];
        }
    }
}
