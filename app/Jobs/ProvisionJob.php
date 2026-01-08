<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\Provisioning\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionJob implements ShouldQueue, ShouldBeUnique
{
    public function uniqueId()
    {
        return 'provision-'.$this->invoice->id;
    }

    public function uniqueFor(): int
    {
        // Keep job unique for 1 hour to avoid duplicate provisioning
        return 3600;
    }

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;

    public int $tries = 3;

    // Backoff in seconds (exponential-ish)
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
        Log::info('ProvisionJob started', ['invoice_id' => $this->invoice->id]);

        // Idempotency: skip if already provisioned
        if ($this->invoice->provisioned_at) {
            Log::info('ProvisionJob skipped, already provisioned', ['invoice_id' => $this->invoice->id]);
            return;
        }

        // Attempt to provision the resource
        $success = $service->provision($this->invoice);

        if ($success) {
            $this->invoice->automation_status = 'provisioned';
            $this->invoice->provisioned_at = now();
            $this->invoice->save();
            Log::info('ProvisionJob completed', ['invoice_id' => $this->invoice->id]);
        } else {
            // Throwing will mark job for retry based on $tries/backoff
            throw new \Exception('Provisioning failed for invoice ' . $this->invoice->id);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('ProvisionJob failed', ['invoice_id' => $this->invoice->id, 'error' => $exception->getMessage()]);

        $this->invoice->automation_status = 'failed';
        $this->invoice->save();

        // Optionally, notify admins or fire an event here
    }
}
