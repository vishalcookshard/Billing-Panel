<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\WebhookEvent;

class WebhookFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_route_processes_event_idempotently()
    {
        // Bind a fake plugin manager that returns a simple plugin
        $this->app->singleton('App\Plugins\PluginManager', function () {
            return new class {
                public function discover() {}
                public function get($key) {
                    return ['class' => \App\Tests\Stubs\FakeWebhookPlugin::class];
                }
            };
        });

        // Register the fake plugin class
        if (!class_exists('\App\Tests\Stubs\FakeWebhookPlugin')) {
            eval("
            namespace App\\Tests\\Stubs;\n\n            use Illuminate\\Http\\Request;\n\n            class FakeWebhookPlugin {\n                public function handleWebhook(Request $request) { return ['handled' => true]; }\n                public function verifyWebhookSignature(Request $request) { return true; }\n            }\n            ");
        }

        $payload = ['id' => 'evt_feature_1'];

        $resp1 = $this->postJson('/api/webhooks/payment/fake', $payload);
        $resp1->assertStatus(200)->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('webhook_events', ['event_id' => 'evt_feature_1', 'processed' => true]);

        // Second post should be duplicate but still return ok
        $resp2 = $this->postJson('/api/webhooks/payment/fake', $payload);
        $resp2->assertStatus(200)->assertJson(['status' => 'ok']);
    }
}
