<?php

namespace App\Plugins\Payments;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function key(): string;
    public function name(): string;
    public function type(): string; // payment
    public function enabled(): bool;

    // Handle incoming webhook; return array with 'handled' and optional 'invoice_id' or 'message'
    public function handleWebhook(Request $request): array;

    // Optional: verify webhook signature and reject unsigned or invalid requests
    public function verifyWebhookSignature(Request $request): bool;

    // Return config array loaded from DB
    public function config(): array;
}
