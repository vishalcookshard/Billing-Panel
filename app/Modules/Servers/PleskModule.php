<?php

namespace App\Modules\Servers;

use App\Contracts\ServerModuleInterface;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PleskModule implements ServerModuleInterface
{
    protected $apiUrl;
    protected $username;
    protected $password;

    public function __construct(array $config)
    {
        $this->apiUrl = $config['api_url'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
    }

    public function create(Service $service): bool
    {
        // Create shared hosting account via Plesk API
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post($this->apiUrl . '/api/v2/customers', [
                    // ...account creation payload from $service->config
                ]);
            if ($response->successful()) {
                $data = $response->json();
                $service->external_id = $data['id'] ?? null;
                $service->username = $data['login'] ?? null;
                $service->password = $data['password'] ?? null;
                $service->status = 'active';
                $service->save();
                return true;
            }
            Log::error('Plesk create failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Plesk create exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function suspend(Service $service): bool
    {
        return $this->changeStatus($service, 'suspend');
    }

    public function unsuspend(Service $service): bool
    {
        return $this->changeStatus($service, 'activate');
    }

    public function terminate(Service $service): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->delete($this->apiUrl . '/api/v2/customers/' . $service->external_id);
            if ($response->successful()) {
                $service->status = 'terminated';
                $service->terminated_at = now();
                $service->save();
                return true;
            }
            Log::error('Plesk terminate failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Plesk terminate exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function getLoginUrl(Service $service): ?string
    {
        return $this->apiUrl . '/login/' . $service->username;
    }

    public function getUsage(Service $service): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get($this->apiUrl . '/api/v2/customers/' . $service->external_id . '/usage');
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
            Log::error('Plesk getUsage exception', ['error' => $e->getMessage()]);
        }
        return [];
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get($this->apiUrl . '/api/v2/server');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Plesk testConnection exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    public function upgrade(Service $service, int $oldPlanId, int $newPlanId): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->patch($this->apiUrl . '/api/v2/customers/' . $service->external_id, [
                    // ...new plan limits from $service->config for $newPlanId
                ]);
            if ($response->successful()) {
                $service->plan_id = $newPlanId;
                $service->save();
                return true;
            }
            Log::error('Plesk upgrade failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::error('Plesk upgrade exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    protected function changeStatus(Service $service, string $action): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post($this->apiUrl . '/api/v2/customers/' . $service->external_id . '/status', [
                    'action' => $action,
                ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Plesk changeStatus exception', ['error' => $e->getMessage()]);
        }
        return false;
    }

    // Additional methods for domain, email, database, FTP, SSL, etc. can be added here
}
