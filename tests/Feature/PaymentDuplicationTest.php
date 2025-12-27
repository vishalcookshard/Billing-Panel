<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\WebhookEvent;

class PaymentDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_payment_event_is_ignored()
    {
        WebhookEvent::create([
            'event_id' => 'evt_duplicate',
            'plugin' => 'stripe',
            'payload' => json_encode(['id' => 'evt_duplicate']),
            'processed' => true,
        ]);
        $response = $this->postJson('/api/webhooks/payment/stripe', [
            'id' => 'evt_duplicate',
            'amount' => 100
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok', 'message' => 'duplicate']);
    }
}
