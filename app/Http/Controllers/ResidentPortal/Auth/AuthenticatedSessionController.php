<?php

namespace App\Http\Controllers\ResidentPortal\Auth;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('ResidentPortal/Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
            ],
            'remember' => [
                'nullable',
                'boolean',
            ],
        ]);

        $login = trim($validated['login']);

        $resident = Resident::query()
            ->where(function ($query) use ($login) {
                $query
                    ->where('resident_code', $login)
                    ->orWhere('phone', $login)
                    ->orWhere('email', $login);
            })
            ->first();

        if (
            !$resident ||
            !Auth::guard('resident')->attempt(
                [
                    'id' => $resident->id,
                    'password' => $validated['password'],
                ],
                (bool) ($validated['remember'] ?? false)
            )
        ) {
            throw ValidationException::withMessages([
                'login' => 'The provided credentials are incorrect.',
            ]);
        }
        if (!$resident->portal_enabled) {
            throw ValidationException::withMessages([
                'login' => 'Your resident portal access has not been enabled.',
            ]);
        }

        if (
            in_array(
                $resident->status,
                ['suspended', 'left', 'inactive'],
                true
            )
        ) {
            Auth::guard('resident')->logout();

            throw ValidationException::withMessages([
                'login' => 'Your resident account is not currently active.',
            ]);
        }

        $request->session()->regenerate();

        $resident->forceFill([
            'last_login_at' => now(),
        ])->save();

        if ($resident->must_change_password) {
            return redirect()->route(
                'resident.password.first-change'
            );
        }

        return redirect()->intended(
            route('resident.dashboard')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('resident')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('resident.login');
    }
}