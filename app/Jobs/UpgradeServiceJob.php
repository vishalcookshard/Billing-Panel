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

class UpgradeServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $serviceId;
    public $oldPlanId;
    public $newPlanId;

    public function __construct($serviceId, $oldPlanId, $newPlanId)
    {
        $this->serviceId = $serviceId;
        $this->oldPlanId = $oldPlanId;
        $this->newPlanId = $newPlanId;
    }

    public function handle(ServerModuleManager $manager)
    {
        $service = Service::find($this->serviceId);
        if (!$service) return;
        $module = $manager->getModule($service->module);
        if ($module) {
            try {
                $success = $module->upgrade($service, $this->oldPlanId, $this->newPlanId);
                if ($success) {
                    $service->plan_id = $this->newPlanId;
                    $service->save();
                    // Fire event: ServiceUpgraded
                } else {
                    Log::error('Upgrade failed', ['service_id' => $service->id]);
                    $this->fail();
                }
            } catch (\Exception $e) {
                Log::error('Upgrade exception', ['service_id' => $service->id, 'error' => $e->getMessage()]);
                $this->fail($e);
            }
        }
    }
}
