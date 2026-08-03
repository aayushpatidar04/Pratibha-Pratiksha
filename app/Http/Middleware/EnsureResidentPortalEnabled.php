<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResidentPortalEnabled
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $resident = Auth::guard('resident')->user();

        if (!$resident) {
            return redirect()->route('resident.login');
        }

        if (!$resident->portal_enabled) {
            Auth::guard('resident')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('resident.login')
                ->withErrors([
                    'login' => 'Your resident portal access is not enabled.',
                ]);
        }

        if (
            in_array(
                $resident->status,
                ['inactive', 'suspended', 'left'],
                true
            )
        ) {
            Auth::guard('resident')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('resident.login')
                ->withErrors([
                    'login' => 'Your resident account is not currently active.',
                ]);
        }

        return $next($request);
    }
}