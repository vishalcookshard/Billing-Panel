<?php
namespace App\Http\Middleware;

use Closure;

class AddRateLimitHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->route()) {
            $limiter = app(\Illuminate\Cache\RateLimiter::class);
            $key = $request->fingerprint();
            $response->headers->set('X-RateLimit-Limit', config('rate-limiting.api.max_attempts'));
            $response->headers->set('X-RateLimit-Remaining', $limiter->remaining($key, config('rate-limiting.api.max_attempts')));
        }

        return $response;
    }
}
