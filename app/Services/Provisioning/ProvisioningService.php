<?php

namespace App\Services\Provisioning;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    protected \App\Plugins\PluginManager $manager;

    public function __construct(\App\Plugins\PluginManager $manager)
    {
        $this->manager = $manager;
        $this->manager->discover();
    }

    protected function pluginForInvoice($invoice)
    {
        $module = $invoice->server_module ?? 'proxmox';
        $meta = $this->manager->get($module);
        return $meta;
    }

    public function createService($invoice): array
    {
        $meta = $this->pluginForInvoice($invoice);

        if (!$meta) {
            Log::error('No server plugin for invoice', ['invoice_id' => $invoice->id, 'module' => $invoice->server_module]);
            return ['success' => false, 'message' => 'no_plugin'];
        }

        // Plugin should provide HTTP API endpoint or class to call; for now we provide a generic hook via a command
        // Real implementation will call plugin's API client
        // For skeleton, return success with a fake service id
        return ['success' => true, 'service_id' => 'srv-' . uniqid()];
    }

    /**
     * Provision resource with a distributed lock to prevent duplicate provisioning
     */
    public function provision($invoice): bool
    {
        $lockKey = 'provision:invoice:' . $invoice->id;
        $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            Log::warning('Could not obtain provisioning lock', ['invoice_id' => $invoice->id]);
            return false;
        }

        try {
            // double-check idempotency
            if ($invoice->provisioned_at) {
                Log::info('Invoice already provisioned, skipping', ['invoice_id' => $invoice->id]);
                return true;
            }

            $res = $this->createService($invoice);

            if ($res['success'] ?? false) {
                // Save result (service_id) atomicly
                \Illuminate\Support\Facades\DB::transaction(function () use ($invoice, $res) {
                    $inv = \App\Models\Invoice::lockForUpdate()->find($invoice->id);
                    if (!$inv) throw new \RuntimeException('Invoice disappeared during provisioning');

                    if ($inv->provisioned_at) {
                        return true;
                    }

                    $inv->service_id = $res['service_id'] ?? $inv->service_id;
                    $inv->automation_status = 'provisioned';
                    $inv->provisioned_at = now();
                    $inv->save();
                });

                return true;
            }

            return false;
        } finally {
            $lock->release();
        }
    }

    public function suspend($invoice): bool
    {
        $meta = $this->pluginForInvoice($invoice);
        if (!$meta) {
            Log::error('No server plugin for suspend', ['invoice_id' => $invoice->id]);
            return false;
        }

        // call plugin suspend API - skeleton
        return true;
    }

    public function unsuspend($invoice): bool
    {
        $meta = $this->pluginForInvoice($invoice);
        if (!$meta) {
            Log::error('No server plugin for unsuspend', ['invoice_id' => $invoice->id]);
            return false;
        }

        return true;
    }

    public function terminate($invoice): bool
    {
        $meta = $this->pluginForInvoice($invoice);
        if (!$meta) {
            Log::error('No server plugin for terminate', ['invoice_id' => $invoice->id]);
            return false;
        }

        return true;
    }
}
