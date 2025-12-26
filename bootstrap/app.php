<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Register core service providers to ensure all bindings (files, cache, etc.) are available
$coreProviders = [
    'Illuminate\Auth\AuthServiceProvider',
    'Illuminate\Broadcasting\BroadcastServiceProvider',
    'Illuminate\Bus\BusServiceProvider',
    'Illuminate\Cache\CacheServiceProvider',
    'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
    'Illuminate\Cookie\CookieServiceProvider',
    'Illuminate\Database\DatabaseServiceProvider',
    'Illuminate\Encryption\EncryptionServiceProvider',
    'Illuminate\Filesystem\FilesystemServiceProvider',
    'Illuminate\Foundation\Providers\FormRequestServiceProvider',
    'Illuminate\Hashing\HashServiceProvider',
    'Illuminate\Mail\MailServiceProvider',
    'Illuminate\Notifications\NotificationServiceProvider',
    'Illuminate\Pagination\PaginationServiceProvider',
    'Illuminate\Pipeline\PipelineServiceProvider',
    'Illuminate\Queue\QueueServiceProvider',
    'Illuminate\Redis\RedisServiceProvider',
    'Illuminate\Auth\Passwords\PasswordResetServiceProvider',
    'Illuminate\Session\SessionServiceProvider',
    'Illuminate\Translation\TranslationServiceProvider',
    'Illuminate\Validation\ValidationServiceProvider',
    'Illuminate\View\ViewServiceProvider',
];

foreach ($coreProviders as $provider) {
    if (class_exists($provider) && !$app->providerIsLoaded($provider)) {
        try {
            $app->register($provider);
        } catch (Throwable $e) {
            // Skip providers that fail to load (may not be relevant to this app)
        }
    }
}

return $app;
