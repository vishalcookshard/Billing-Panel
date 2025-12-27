<?php
return [
    'channels' => [
        'discord' => [
            'enabled' => env('DISCORD_NOTIFICATIONS_ENABLED', false),
            'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        ],
        'email' => [
            'enabled' => env('EMAIL_NOTIFICATIONS_ENABLED', true),
            'recipients' => explode(',', env('ADMIN_EMAILS', 'admin@example.com')),
        ],
        'slack' => [
            'enabled' => env('SLACK_NOTIFICATIONS_ENABLED', false),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
        ],
    ],
    'job_failures' => [
        'notify' => env('NOTIFY_JOB_FAILURES', true),
        'include_details' => env('INCLUDE_JOB_FAILURE_DETAILS', true),
    ],
];
