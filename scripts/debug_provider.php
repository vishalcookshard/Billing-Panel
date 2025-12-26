<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
try {
    echo "Attempting to register providers from config...\n";
    $providers = require __DIR__ . '/../config/app.php';
    foreach ($providers['providers'] as $provider) {
        echo "Registering: $provider\n";
        try {
            $app->register($provider);
            echo "  Registered OK\n";
        } catch (Throwable $e) {
            echo "  Failed to register $provider: " . get_class($e) . " - " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
        }
    }
} catch (Throwable $e) {
    echo "Top-level error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
