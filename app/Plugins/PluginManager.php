use App\Plugins\PluginValidator;
    public function register(string $pluginClass): void
    {
        $validator = new PluginValidator();
        $errors = $validator->validate($pluginClass);
        if (!empty($errors)) {
            Log::error('Plugin validation failed', [
                'plugin' => $pluginClass,
                'errors' => $errors
            ]);
            throw new \RuntimeException('Plugin validation failed: ' . implode(', ', $errors));
        }
        // Continue with registration...
    }
<?php

namespace App\Plugins;

use App\Models\Plugin;
use Illuminate\Support\Facades\Log;

class PluginManager
{
    protected array $plugins = [];

    public function discover(string $path = null): array
    {
        $path = $path ?? base_path('plugins');

        if (!is_dir($path)) {
            return [];
        }

        $folders = scandir($path);

        foreach ($folders as $folder) {
            if (in_array($folder, ['.', '..'])) {
                continue;
            }

            $metaFile = $path . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'plugin.json';

            if (is_file($metaFile)) {
                $meta = json_decode(file_get_contents($metaFile), true);

                if (!$meta) {
                    Log::warning('Invalid plugin.json', ['path' => $metaFile]);
                    continue;
                }

                $this->plugins[$meta['key']] = $meta;

                // ensure DB record exists
                Plugin::updateOrCreate(['key' => $meta['key']], [
                    'name' => $meta['name'] ?? $meta['key'],
                    'type' => $meta['type'] ?? 'unknown',
                ]);
            }
        }

        return $this->plugins;
    }

    public function get(string $key): ?array
    {
        return $this->plugins[$key] ?? null;
    }
}
