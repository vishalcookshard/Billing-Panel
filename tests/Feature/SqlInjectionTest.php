<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Plan;

class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_rejects_sql_injection()
    {
        $plan = Plan::factory()->create(['is_active' => true]);
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $response = $this->post('/checkout/' . $plan->id, [
            'billing_cycle' => "monthly'; DROP TABLE users; --",
            'payment_method' => 'card',
            'gateway' => 'stripe',
            'amount' => 10.00,
        ]);
        $response->assertSessionHasErrors('billing_cycle');
    }
}
