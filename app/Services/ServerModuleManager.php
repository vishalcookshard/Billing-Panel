<?php

namespace App\Services;

use App\Contracts\ServerModuleInterface;
use Illuminate\Support\Facades\Log;

class ServerModuleManager
{
    protected $modules = [];
    protected $configurations = [];

    public function registerModule(string $name, ServerModuleInterface $module, array $config = [])
    {
        $this->modules[$name] = $module;
        $this->configurations[$name] = $config;
    }

    public function getModule(string $name): ?ServerModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    public function getConfig(string $name): array
    {
        return $this->configurations[$name] ?? [];
    }

    public function testModule(string $name): bool
    {
        $module = $this->getModule($name);
        if ($module) {
            return $module->testConnection();
        }
        return false;
    }

    public function healthCheck(): array
    {
        $results = [];
        foreach ($this->modules as $name => $module) {
            try {
                $results[$name] = $module->testConnection();
            } catch (\Exception $e) {
                Log::error('Module health check failed', ['module' => $name, 'error' => $e->getMessage()]);
                $results[$name] = false;
            }
        }
        return $results;
    }

    public function logError(string $module, string $message, array $context = [])
    {
        Log::error("[$module] $message", $context);
    }
}
