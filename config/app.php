<?php

return [
    'name' => env('APP_NAME', 'Billing-Panel'),
    'env' => env('APP_ENV', 'production'),

    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\FrameworkServiceProvider::class,
        App\Providers\PluginServiceProvider::class,
    ],
];
