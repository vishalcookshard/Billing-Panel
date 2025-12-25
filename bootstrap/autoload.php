<?php

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the auto loader classes for the application and vendor packages.
require __DIR__.'/../vendor/autoload.php';

// Bootstrap the application and get the service container...
$app = require_once __DIR__.'/../bootstrap/app.php';

return $app;
