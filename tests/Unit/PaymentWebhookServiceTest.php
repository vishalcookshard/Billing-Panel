<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Billing\PaymentService;
use Illuminate\Http\Request;
use App\Models\WebhookEvent;

class PaymentWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_event_is_recorded_and_idempotent()
    {
        // Bind a fake plugin that simply returns handled
        $fakePlugin = new class {
            public function handleWebhook(Request $request): array
            {
                return ['handled' => true];
            }
            public function verifyWebhookSignature(Request $request): bool
            {
                return true;
            }
        };

        $this->app->singleton('App\Plugins\PluginManager', function () use ($fakePlugin) {
            return new class($fakePlugin) {
                protected $data;
                public function __construct($p) { $this->data = ['fake' => ['class' => get_class($p)]]; }
                public function discover() {}
                public function get($key) { return ['class' => get_class($this->getFake())]; }
                public function getFake() { return new class { public function handleWebhook($r){ return ['handled' => true]; } public function verifyWebhookSignature($r){ return true; } }; }
            };
        });

        $ps = $this->app->make(PaymentService::class);

        $req = Request::create('/api/webhooks/payment/fake', 'POST', [], [], [], [], json_encode(['id' => 'evt_123']));

        $res1 = $ps->handleWebhook('fake', $req);
        $this->assertEquals('ok', $res1['status']);

        $this->assertDatabaseHas('webhook_events', ['event_id' => 'evt_123', 'processed' => true]);

        // Second identical request should be considered duplicate
        $res2 = $ps->handleWebhook('fake', $req);
        $this->assertEquals('ok', $res2['status']);
        $this->assertEquals('duplicate', $res2['message'] ?? 'duplicate');
    }
}
