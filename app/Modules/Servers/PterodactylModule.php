<?php

namespace App\Modules\Servers;

use App\Contracts\ServerModuleInterface;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PterodactylModule implements ServerModuleInterface
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct(array $config)
    {
        $this->apiUrl = $config['api_url'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
    }

    public function create(Service $service): bool
    {
        // Provision game server via Pterodactyl API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/api/application/servers', [
                // ...server creation payload from $service->config
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $service->external_id = $data['attributes']['id'] ?? null;
                $service->username = $data['attributes']['user'] ?? null;
                $service->password = null;
                $service->status = 'active';
                $service->save();
                return true;
            }
            Log::error('Pterodactyl create failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Pterodactyl create exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function suspend(Service $service): bool
    {
        // Suspend server (stop power)
        return $this->powerAction($service, 'stop');
    }

    public function unsuspend(Service $service): bool
    {
        // Unsuspend server (start power)
        return $this->powerAction($service, 'start');
    }

    public function terminate(Service $service): bool
    {
        // Delete server
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->delete($this->apiUrl . '/api/application/servers/' . $service->external_id);
            if ($response->successful()) {
                $service->status = 'terminated';
                $service->terminated_at = now();
                $service->save();
                return true;
            }
            Log::error('Pterodactyl terminate failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Pterodactyl terminate exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function getLoginUrl(Service $service): ?string
    {
        // Return control panel login URL
        return $this->apiUrl . '/server/' . $service->external_id;
    }

    public function getUsage(Service $service): array
    {
        // Get resource usage stats
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/api/client/servers/' . $service->external_id . '/resources');
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'cpu' => $data['attributes']['cpu_usage'] ?? 0,
                    'ram' => $data['attributes']['memory_usage'] ?? 0,
                    'disk' => $data['attributes']['disk_usage'] ?? 0,
                    'bandwidth' => $data['attributes']['network_rx'] ?? 0,
                    'uptime' => $data['attributes']['uptime'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pterodactyl getUsage exception', ['error' => $e->getMessage()]);
        }
        return [];
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/api/application/nests');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Pterodactyl testConnection exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function upgrade(Service $service, int $oldPlanId, int $newPlanId): bool
    {
        // Upgrade server resources
        try {
            $payload = [
                // ...resource limits from $service->config for $newPlanId
            ];
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->patch($this->apiUrl . '/api/application/servers/' . $service->external_id, $payload);
            if ($response->successful()) {
                $service->plan_id = $newPlanId;
                $service->save();
                return true;
            }
            Log::error('Pterodactyl upgrade failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Pterodactyl upgrade exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    protected function powerAction(Service $service, string $action): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/api/client/servers/' . $service->external_id . '/power', [
                'signal' => $action,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Pterodactyl powerAction exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    // Additional methods for file management, backups, database creation, subdomain/port allocation can be added here
}
