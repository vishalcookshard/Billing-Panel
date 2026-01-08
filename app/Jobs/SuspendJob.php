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

class SuspendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function handle(ProvisioningService $service)
    {
        Log::info('SuspendJob started', ['invoice_id' => $this->invoice->id]);

        $success = $service->suspend($this->invoice);

        if ($success) {
            $this->invoice->automation_status = 'suspended';
            $this->invoice->save();
            Log::info('SuspendJob completed', ['invoice_id' => $this->invoice->id]);
        } else {
            throw new \Exception('Suspension failed for invoice ' . $this->invoice->id);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SuspendJob failed', ['invoice_id' => $this->invoice->id, 'error' => $exception->getMessage()]);

        $this->invoice->automation_status = 'failed';
        $this->invoice->save();
    }
}
