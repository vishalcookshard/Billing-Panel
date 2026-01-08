<?php

namespace App\Modules\Servers;

use App\Contracts\ServerModuleInterface;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class VirtualizorModule implements ServerModuleInterface
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiPass;

    public function __construct(array $config)
    {
        $this->apiUrl = $config['api_url'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->apiPass = $config['api_pass'] ?? '';
    }

    public function create(Service $service): bool
    {
        // VPS creation via Virtualizor API
        try {
            $response = Http::post($this->apiUrl . '/index.php?act=addvs', [
                'api_key' => $this->apiKey,
                'api_pass' => $this->apiPass,
                // ...other VPS params from $service->config
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $service->external_id = $data['vpsid'] ?? null;
                $service->username = $data['username'] ?? null;
                $service->password = $data['password'] ?? null;
                $service->status = 'active';
                $service->save();
                return true;
            }
            Log::error('Virtualizor create failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Virtualizor create exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function suspend(Service $service): bool
    {
        return $this->powerAction($service, 'stop');
    }

    public function unsuspend(Service $service): bool
    {
        return $this->powerAction($service, 'start');
    }

    public function terminate(Service $service): bool
    {
        try {
            $response = Http::post($this->apiUrl . '/index.php?act=delvs', [
                'api_key' => $this->apiKey,
                'api_pass' => $this->apiPass,
                'vpsid' => $service->external_id,
            ]);
            if ($response->successful()) {
                $service->status = 'terminated';
                $service->terminated_at = now();
                $service->save();
                return true;
            }
            Log::error('Virtualizor terminate failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Virtualizor terminate exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function getLoginUrl(Service $service): ?string
    {
        return $this->apiUrl . '/client/?vpsid=' . $service->external_id;
    }

    public function getUsage(Service $service): array
    {
        try {
            $response = Http::post($this->apiUrl . '/index.php?act=vs', [
                'api_key' => $this->apiKey,
                'api_pass' => $this->apiPass,
                'vpsid' => $service->external_id,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'cpu' => $data['cpu'] ?? 0,
                    'ram' => $data['ram'] ?? 0,
                    'disk' => $data['disk'] ?? 0,
                    'bandwidth' => $data['bandwidth'] ?? 0,
                    'uptime' => $data['uptime'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Virtualizor getUsage exception', ['error' => $e->getMessage()]);
        }
        return [];
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::post($this->apiUrl . '/index.php?act=ostemplates', [
                'api_key' => $this->apiKey,
                'api_pass' => $this->apiPass,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Virtualizor testConnection exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function upgrade(Service $service, int $oldPlanId, int $newPlanId): bool
    {
        try {
            $response = Http::post($this->apiUrl . '/index.php?act=editvs', [
                'api_key' => $this->apiKey,
                'api_pass' => $this->apiPass,
                'vpsid' => $service->external_id,
                // ...new resource limits from $service->config for $newPlanId
            ]);
            if ($response->successful()) {
                $service->plan_id = $newPlanId;
                $service->save();
                return true;
            }
            Log::error('Virtualizor upgrade failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Virtualizor upgrade exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    protected function powerAction(Service $service, string $action): bool
    {
        try {
            $response = Http::post($this->apiUrl . '/index.php?act=power', [
                'api_key' => $this->apiKey,
                'api_pass' => $this->apiPass,
                'vpsid' => $service->external_id,
                'action' => $action,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Virtualizor powerAction exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    // Additional methods for VNC, snapshots, rebuild, etc. can be added here
}
