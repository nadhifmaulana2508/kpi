<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use App\Models\SsoIdentity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveEmployee
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user();
        $hasActiveAssignment = $employee instanceof Employee
            && $employee->demo_only
            && $employee->active
            && $employee->activeAssignment()
                ->demoOnly()
                ->exists();
        $ssoIdentityId = $request->hasSession() ? $request->session()->get('sso_identity_id') : null;
        $hasActiveSsoIdentity = $ssoIdentityId === null;

        if ($ssoIdentityId !== null && $employee instanceof Employee) {
            $hasActiveSsoIdentity = SsoIdentity::query()
                ->whereKey($ssoIdentityId)
                ->where('employee_id', $employee->id)
                ->where('active', true)
                ->exists();
        }

        if (! $hasActiveAssignment || ! $hasActiveSsoIdentity) {
            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(403, 'Akses demo tidak aktif atau penugasan utama tidak berlaku.');
        }

        return $next($request);
    }
}
