<?php

namespace App\Plugins\Payments;

use Illuminate\Http\Request;
use App\Models\Plugin;
use App\Models\PluginConfig;
use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use Illuminate\Support\Facades\Log;
use App\Events\InvoicePaid;

class StripePlugin implements PaymentGatewayInterface
{
    public function key(): string
    {
        return 'stripe';
    }

    public function name(): string
    {
        return 'Stripe';
    }

    public function type(): string
    {
        return 'payment';
    }

    public function enabled(): bool
    {
        $p = Plugin::where('key', $this->key())->first();
        return $p ? (bool)$p->enabled : false;
    }

    public function config(): array
    {
        $plugin = Plugin::where('key', $this->key())->first();
        if (!$plugin) {
            return [];
        }

        $configs = PluginConfig::where('plugin_id', $plugin->id)->get()->pluck('value', 'key')->toArray();

        // decrypt encrypted values
        foreach (PluginConfig::where('plugin_id', $plugin->id)->where('encrypted', true)->get() as $row) {
            try {
                $configs[$row->key] = decrypt($row->value);
            } catch (\Throwable $e) {
                Log::error('Failed to decrypt plugin config', ['plugin' => $this->key(), 'key' => $row->key]);
            }
        }

        return $configs;
    }

    public function handleWebhook(Request $request): array
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $config = $this->config();
        $secret = $config['webhook_secret'] ?? null;

        if (!$secret) {
            Log::warning('Stripe webhook received but no webhook secret configured');
            return ['handled' => false, 'message' => 'no_secret'];
        }

        if (!$this->verifySignature($payload, $sigHeader, $secret)) {
            Log::warning('Stripe webhook signature verification failed');
            return ['handled' => false, 'message' => 'signature_invalid'];
        }

        // expose verification method via the interface
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $config = $this->config();
        $secret = $config['webhook_secret'] ?? null;
        if (!$secret) return false;
        return $this->verifySignature($payload, $sigHeader, $secret);
    }

        $data = json_decode($payload, true);
        if (!$data) {
            Log::warning('Stripe webhook invalid payload');
            return ['handled' => false, 'message' => 'invalid_payload'];
        }

        // Handle charge/successful payment events
        $type = $data['type'] ?? null;
        $object = $data['data']['object'] ?? [];

        // Try to locate invoice_id in metadata
        $invoiceId = $object['metadata']['invoice_id'] ?? ($object['invoice'] ?? null);

        if (!$invoiceId && isset($object['payment_intent'])) {
            // fallback: look up metadata on payment intent - not implemented in skeleton
        }

        if ($type === 'charge.succeeded' || $type === 'payment_intent.succeeded') {
            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice) {
                    $idempotency = $data['id'] ?? null;

                    $service = app(InvoiceService::class);
                    try {
                        $processed = $service->markPaid($invoice, $object, $idempotency);
                        if ($processed) {
                            InvoicePaid::dispatch($invoice);
                            Log::info('Stripe webhook marked invoice paid', ['invoice_id' => $invoice->id]);
                        } else {
                            Log::info('Stripe webhook already processed or idempotent skip', ['invoice_id' => $invoice->id]);
                        }

                        return ['handled' => true, 'invoice_id' => $invoice->id, 'processed' => $processed];
                    } catch (\Throwable $e) {
                        Log::error('Stripe payment processing error', ['error' => $e->getMessage(), 'invoice_id' => $invoice->id]);
                        return ['handled' => false, 'message' => 'processing_error'];
                    }
                }
            }

            return ['handled' => false, 'message' => 'no_invoice_found'];
        }

        return ['handled' => false, 'message' => 'event_not_handled'];
    }

    // Implements Stripe's signature verification logic (tolerance 5 minutes)
    protected function verifySignature(string $payload, ?string $sigHeader, string $secret, int $tolerance = 300): bool
    {
        if (!$sigHeader) {
            return false;
        }

        // parse header: t=timestamp,v1=...,v0=...
        $parts = explode(',', $sigHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $p) {
            [$k, $v] = explode('=', $p, 2) + [null, null];
            if ($k === 't') $timestamp = (int)$v;
            if (strpos($k, 'v') === 0) $signatures[] = $v;
        }

        if (!$timestamp) return false;

        if (abs(time() - $timestamp) > $tolerance) return false;

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        foreach ($signatures as $s) {
            if (hash_equals($expected, $s)) return true;
        }

        return false;
    }
}
