<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login first');
        }

        // Super admin bypass
        if ($user->is_admin && $user->email === env('SUPER_ADMIN_EMAIL', 'admin@example.com')) {
            return $next($request);
        }

        // Check permission
        if (!$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
