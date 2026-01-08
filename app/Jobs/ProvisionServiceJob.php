<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\ServerModuleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ProvisionServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $serviceId;

    public function __construct($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    public function handle(ServerModuleManager $manager)
    {
        $service = Service::find($this->serviceId);
        if (!$service) return;
        $module = $manager->getModule($service->module);
        if ($module) {
            try {
                $success = $module->create($service);
                if ($success) {
                    // Fire event: ServiceProvisioned
                } else {
                    Log::error('Provision failed', ['service_id' => $service->id]);
                    $this->fail();
                }
            } catch (\Exception $e) {
                Log::error('Provision exception', ['service_id' => $service->id, 'error' => $e->getMessage()]);
                $this->fail($e);
            }
        }
    }
}
