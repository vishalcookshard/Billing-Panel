<?php

use Illuminate\Support\Facades\Log;

if (!function_exists('log_security_event')) {
    /**
     * Log a security event to the security channel.
     *
     * @param string $event
     * @param array $context
     * @return void
     */
    function log_security_event(string $event, array $context = [])
    {
        $request = request();
        $user = $request ? $request->user() : null;

        $context = array_merge([
            'ip'        => $request ? $request->ip() : null,
            'user_id'   => $user ? $user->id : null,
            'user_agent'=> $request ? $request->userAgent() : null,
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel('security')->info($event, $context);
    }
}
