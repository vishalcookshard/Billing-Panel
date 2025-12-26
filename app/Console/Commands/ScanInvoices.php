<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\AdminSetting;
use App\Jobs\SuspendServiceJob;
use App\Jobs\TerminateServiceJob;
use App\Events\InvoiceOverdue;
use App\Events\InvoiceGraceWarning;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScanInvoices extends Command
{
    protected $signature = 'invoices:scan';

    protected $description = 'Scan invoices to send warnings, move through grace periods, suspend or terminate services based on configured thresholds';

    public function handle()
    {
        $this->info('Running invoice scan...');

        $graceDays = (int) AdminSetting::get('grace_days', 7);
        $warningDays = (int) AdminSetting::get('warning_days', 3);
        $autoTerminateDays = (int) AdminSetting::get('auto_terminate_days', 30);

        $today = Carbon::now();

        // 1) Identify invoices that have gone past due and are still unpaid -> warn
        $pastDue = Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_UNPAID])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->get();

        foreach ($pastDue as $invoice) {
            try {
                $invoice->transitionTo(Invoice::STATUS_WARNED);
                InvoiceGraceWarning::dispatch($invoice);
                $invoice->grace_notified_at = now();
                $invoice->save();
                Log::info('Invoice marked warned and grace warning sent', ['invoice_id' => $invoice->id]);
            } catch (\Throwable $e) {
                Log::warning('Skipping warn transition for invoice', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }
        }

        // 2) Move warned invoices into grace phase when appropriate
        $warned = Invoice::where('status', Invoice::STATUS_WARNED)
            ->whereNotNull('due_date')
            ->get();

        foreach ($warned as $invoice) {
            $daysOverdue = $invoice->due_date ? $today->diffInDays($invoice->due_date) : null;
            if ($daysOverdue !== null && $daysOverdue >= $warningDays) {
                try {
                    $invoice->transitionTo(Invoice::STATUS_GRACE);
                    Log::info('Invoice moved to grace', ['invoice_id' => $invoice->id]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to move invoice to grace', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
                }
            }
        }

        // 3) Suspend invoices that have exceeded grace period
        $toSuspend = Invoice::where('status', Invoice::STATUS_GRACE)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today->copy()->subDays($graceDays))
            ->get();

        foreach ($toSuspend as $invoice) {
            try {
                $invoice->transitionTo(Invoice::STATUS_SUSPENDED);
                SuspendServiceJob::dispatch($invoice)->onQueue('billing');
                Log::info('SuspendServiceJob dispatched due to grace expiry', ['invoice_id' => $invoice->id]);
            } catch (\Throwable $e) {
                Log::warning('Failed to suspend invoice', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }
        }

        // 4) Terminate after autoTerminateDays elapsed since due date
        $toTerminate = Invoice::where('status', Invoice::STATUS_SUSPENDED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today->copy()->subDays($autoTerminateDays))
            ->get();

        foreach ($toTerminate as $invoice) {
            try {
                $invoice->transitionTo(Invoice::STATUS_TERMINATED);
                TerminateServiceJob::dispatch($invoice)->onQueue('billing');
                Log::info('TerminateServiceJob dispatched due to auto-terminate', ['invoice_id' => $invoice->id]);
            } catch (\Throwable $e) {
                Log::warning('Failed to terminate invoice', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info('Invoice scan completed.');
    }
}
