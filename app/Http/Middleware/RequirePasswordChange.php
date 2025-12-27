<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RequirePasswordChange
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->password_change_required) {
            if (!$request->is('password/change') && !$request->is('logout')) {
                return redirect()->route('password.change')
                    ->with('warning', 'You must change your default password before continuing.');
            }
        }
        return $next($request);
    }
}
