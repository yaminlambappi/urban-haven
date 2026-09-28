<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->hasRole('owner_admin') && ! $user->hasMfaEnabled()) {
            if (! $request->routeIs('admin.mfa.*') && ! $request->routeIs('admin.logout')) {
                return redirect()->route('admin.mfa.enroll');
            }
        }

        if ($user->hasMfaEnabled() && $request->session()->get('mfa_passed') !== true) {
            if (! $request->routeIs('admin.mfa.challenge') && ! $request->routeIs('admin.mfa.verify') && ! $request->routeIs('admin.logout')) {
                return redirect()->route('admin.mfa.challenge');
            }
        }

        return $next($request);
    }
}
