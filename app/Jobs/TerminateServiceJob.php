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

class TerminateServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;

    public int $tries = 2;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice->fresh();
    }

    public function handle(ProvisioningService $service)
    {
        Log::info('TerminateServiceJob started', ['invoice_id' => $this->invoice->id]);

        // If already terminated, skip
        if ($this->invoice->automation_status === 'terminated' || !$this->invoice->service_id) {
            Log::info('TerminateServiceJob skipped: already terminated or no service', ['invoice_id' => $this->invoice->id]);
            return;
        }

        $success = $service->terminate($this->invoice);

        if ($success) {
            $this->invoice->automation_status = 'terminated';
            $this->invoice->service_id = null;
            $this->invoice->save();
            Log::info('TerminateServiceJob completed', ['invoice_id' => $this->invoice->id]);
        } else {
            throw new \Exception('Termination failed for invoice ' . $this->invoice->id);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('TerminateServiceJob failed', ['invoice_id' => $this->invoice->id, 'error' => $exception->getMessage()]);

        $this->invoice->automation_status = 'failed';
        $this->invoice->save();
    }
}
