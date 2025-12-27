<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2); // ms

        $user = $request->user();
        $userId = $user ? $user->id : null;

        Log::channel('api')->info('API Request', [
            'method'     => $request->method(),
            'url'        => $request->fullUrl(),
            'ip'         => $request->ip(),
            'status'     => $response->getStatusCode(),
            'user_id'    => $userId,
            'duration_ms'=> $duration,
            'timestamp'  => Carbon::now()->toIso8601String(),
        ]);

        return $response;
    }
}
