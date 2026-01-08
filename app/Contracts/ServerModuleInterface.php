<?php

namespace App\Contracts;

use App\Models\Service;

interface ServerModuleInterface
{
    /**
     * Provision a new service.
     */
    public function create(Service $service): bool;

    /**
     * Suspend a service.
     */
    public function suspend(Service $service): bool;

    /**
     * Unsuspend/reactivate a service.
     */
    public function unsuspend(Service $service): bool;

    /**
     * Terminate/delete a service.
     */
    public function terminate(Service $service): bool;

    /**
     * Get the control panel login URL for the service.
     */
    public function getLoginUrl(Service $service): ?string;

    /**
     * Get resource usage stats for the service.
     * Should return an array with keys: cpu, ram, disk, bandwidth, uptime, etc.
     */
    public function getUsage(Service $service): array;

    /**
     * Test API connectivity for the module.
     */
    public function testConnection(): bool;

    /**
     * Upgrade the service to a new plan.
     */
    public function upgrade(Service $service, int $oldPlanId, int $newPlanId): bool;
}
