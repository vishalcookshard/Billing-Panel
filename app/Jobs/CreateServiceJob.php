<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\Provisioning\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;

    public int $tries = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice->fresh();
    }

    public function handle(ProvisioningService $service)
    {
        Log::info('CreateServiceJob started', ['invoice_id' => $this->invoice->id]);

        // Idempotency check: do nothing if service already exists
        if ($this->invoice->service_id) {
            Log::info('CreateServiceJob skipped: service already exists', ['invoice_id' => $this->invoice->id]);
            return;
        }

        $result = $service->createService($this->invoice);

        if ($result['success']) {
            $this->invoice->service_id = $result['service_id'];
            $this->invoice->automation_status = 'active';
            $this->invoice->save();
            Log::info('CreateServiceJob completed', ['invoice_id' => $this->invoice->id, 'service_id' => $result['service_id']]);
        } else {
            throw new \Exception('Service creation failed: ' . ($result['message'] ?? 'unknown'));
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('CreateServiceJob failed', ['invoice_id' => $this->invoice->id, 'error' => $exception->getMessage()]);

        $this->invoice->automation_status = 'failed';
        $this->invoice->save();
    }
}
