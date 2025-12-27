<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_blocks_excessive_requests()
    {
        $key = 'api:webhooks:stripe';
        RateLimiter::clear($key);
        $maxAttempts = config('rate-limiting.api.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->postJson('/api/webhooks/payment/stripe', ['id' => 'evt_test_' . $i]);
        }
        $response = $this->postJson('/api/webhooks/payment/stripe', ['id' => 'evt_test_blocked']);
        $response->assertStatus(429);
    }

    public function test_rate_limit_resets()
    {
        $key = 'api:webhooks:stripe';
        RateLimiter::clear($key);
        $maxAttempts = config('rate-limiting.api.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->postJson('/api/webhooks/payment/stripe', ['id' => 'evt_test_' . $i]);
        }
        sleep(config('rate-limiting.api.decay_minutes', 1) * 60);
        $response = $this->postJson('/api/webhooks/payment/stripe', ['id' => 'evt_test_reset']);
        $response->assertStatus(200);
    }
}
