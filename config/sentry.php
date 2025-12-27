<?php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('APP_ENV', 'production'),
    'breadcrumbs' => [
        'sql_bindings' => true,
    ],
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.0),
    'send_default_pii' => false,
    'attach_stacktrace' => true,
    // 'before_send' closure removed for config:cache safety. See README for details.
];
