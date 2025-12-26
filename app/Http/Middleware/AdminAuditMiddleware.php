<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

class AdminAuditMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $user = Auth::user();
            if ($user && ($user->is_admin ?? false || method_exists($user, 'hasPermission'))) {
                // allow recording for admins or permissioned users
                $allowed = $user->is_admin ?? false || ($user->hasPermission('manage-settings') ?? false);
                if ($allowed) {
                    Audit::create([
                        'invoice_id' => $request->route('invoice') ?? null,
                        'user_id' => $user->id,
                        'event' => 'admin_action',
                        'action' => $request->method() . ' ' . $request->path(),
                        'meta' => json_encode(['payload' => $request->except(['password', '_token'])]),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // swallow to avoid breaking admin actions
        }

        return $response;
    }
}
