<?php
return [
    'api' => [
        'enabled' => env('API_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('API_RATE_LIMIT', 60),
        'decay_minutes' => env('API_RATE_LIMIT_DECAY', 1),
    ],
    'webhooks' => [
        'enabled' => env('WEBHOOK_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('WEBHOOK_RATE_LIMIT', 100),
        'decay_minutes' => env('WEBHOOK_RATE_LIMIT_DECAY', 1),
    ],
    'auth' => [
        'login' => [
            'max_attempts' => env('LOGIN_RATE_LIMIT', 5),
            'decay_minutes' => env('LOGIN_RATE_LIMIT_DECAY', 15),
        ],
        'register' => [
            'max_attempts' => env('REGISTER_RATE_LIMIT', 3),
            'decay_minutes' => env('REGISTER_RATE_LIMIT_DECAY', 60),
        ],
    ],
];
