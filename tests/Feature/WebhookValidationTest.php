<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\WebhookEvent;

class WebhookValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_invalid_signature()
    {
        $response = $this->postJson('/api/webhooks/payment/stripe', [
            'id' => 'evt_test_invalid',
            'amount' => 100
        ], [
            'X-Signature' => 'bad_signature'
        ]);
        $response->assertStatus(401);
        $response->assertJson(['status' => 'error', 'message' => 'signature_invalid']);
    }

    public function test_rejects_malformed_payload()
    {
        $response = $this->post('/api/webhooks/payment/stripe', '{not_json}', ['Content-Type' => 'application/json']);
        $response->assertStatus(400);
        $response->assertJson(['status' => 'error', 'message' => 'malformed_payload']);
    }

    public function test_idempotency_duplicate_event()
    {
        WebhookEvent::create([
            'event_id' => 'evt_test_duplicate',
            'plugin' => 'stripe',
            'payload' => json_encode(['id' => 'evt_test_duplicate']),
            'processed' => true,
        ]);
        $response = $this->postJson('/api/webhooks/payment/stripe', [
            'id' => 'evt_test_duplicate',
            'amount' => 100
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok', 'message' => 'duplicate']);
    }
}
