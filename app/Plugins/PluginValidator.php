<?php
namespace App\Plugins;

class PluginValidator
{
    public function validate(string $pluginClass): array
    {
        $errors = [];

        // Check if class exists
        if (!class_exists($pluginClass)) {
            $errors[] = "Plugin class does not exist: {$pluginClass}";
            return $errors;
        }

        // Check if implements interface
        if (!in_array(PluginInterface::class, class_implements($pluginClass))) {
            $errors[] = "Plugin must implement PluginInterface";
        }

        // Check required methods
        $requiredMethods = ['getName', 'getVersion', 'boot'];
        foreach ($requiredMethods as $method) {
            if (!method_exists($pluginClass, $method)) {
                $errors[] = "Plugin missing required method: {$method}";
            }
        }

        // Security checks
        $reflection = new \ReflectionClass($pluginClass);
        $sourceFile = $reflection->getFileName();
        // Check if plugin is in allowed directory
        if (!str_starts_with($sourceFile, base_path('app/Plugins')) &&
            !str_starts_with($sourceFile, base_path('plugins'))) {
            $errors[] = "Plugin must be located in app/Plugins or plugins directory";
        }

        return $errors;
    }

    public function isConfigSafe(array $config): bool
    {
        // Check for suspicious config values
        $suspiciousKeys = ['eval', 'exec', 'system', 'passthru', 'shell_exec'];
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousKeys as $suspicious) {
                    if (stripos($value, $suspicious) !== false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}
