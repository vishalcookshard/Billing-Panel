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
            return redirect('/')->with('error', 'Unauthorized');
        }

        if ($user->hasPermission($permission) || $user->is_admin) {
            return $next($request);
        }

        return redirect('/')->with('error', 'Insufficient permissions');
    }
}
