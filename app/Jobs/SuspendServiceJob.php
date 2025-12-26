<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SuspendServiceJob implements ShouldQueue, ShouldBeUnique
{
    public function uniqueId()
    {
        return 'suspend-'.$this->invoice->id;
    }

    public function uniqueFor(): int
    {
        return 3600;
    }
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
        Log::info('SuspendServiceJob started', ['invoice_id' => $this->invoice->id]);

        // Idempotent: if already suspended, skip
        if ($this->invoice->automation_status === 'suspended') {
            Log::info('SuspendServiceJob skipped: already suspended', ['invoice_id' => $this->invoice->id]);
            return;
        }

        $success = $service->suspend($this->invoice);

        if ($success) {
            $this->invoice->automation_status = 'suspended';
            $this->invoice->save();
            Log::info('SuspendServiceJob completed', ['invoice_id' => $this->invoice->id]);
        } else {
            throw new \Exception('Suspension failed for invoice ' . $this->invoice->id);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SuspendServiceJob failed', ['invoice_id' => $this->invoice->id, 'error' => $exception->getMessage()]);

        $this->invoice->automation_status = 'failed';
        $this->invoice->save();
    }
}
