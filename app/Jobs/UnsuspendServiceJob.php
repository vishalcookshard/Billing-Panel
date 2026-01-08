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

class UnsuspendServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;

    public int $tries = 3;
    public int $timeout = 60;

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
        Log::info('UnsuspendServiceJob started', ['invoice_id' => $this->invoice->id]);

        // If not suspended, skip
        if ($this->invoice->automation_status !== 'suspended') {
            Log::info('UnsuspendServiceJob skipped: not suspended', ['invoice_id' => $this->invoice->id, 'status' => $this->invoice->automation_status]);
            return;
        }

        $success = $service->unsuspend($this->invoice);

        if ($success) {
            $this->invoice->automation_status = 'active';
            $this->invoice->save();
            Log::info('UnsuspendServiceJob completed', ['invoice_id' => $this->invoice->id]);
        } else {
            throw new \Exception('Unsuspend failed for invoice ' . $this->invoice->id);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('UnsuspendServiceJob failed', ['invoice_id' => $this->invoice->id, 'error' => $exception->getMessage()]);

        $this->invoice->automation_status = 'failed';
        $this->invoice->save();
    }
}
