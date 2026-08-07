<?php

namespace App\Http\Controllers\ResidentPortal\Auth;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
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

    public function firstChange(): Response|RedirectResponse
    {
        $resident = Auth::guard('resident')->user();

        if (!$resident) {
            return redirect()->route('resident.login');
        }

        // If password change is already completed,
        // don't allow this page to be accessed again.
        if (!$resident->must_change_password) {
            return redirect()->route('resident.dashboard');
        }

        return Inertia::render('ResidentPortal/Auth/FirstChangePassword');
    }

    /**
     * Update the resident's password for the first time.
     */
    public function updateFirstChange(Request $request): RedirectResponse
    {
        $resident = Auth::guard('resident')->user();

        if (!$resident) {
            return redirect()->route('login');
        }

        if (!$resident->must_change_password) {
            return redirect()->route('resident.dashboard');
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],
        ], [
            'password.confirmed' =>
                'The password confirmation does not match.',
        ]);

        $resident->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        // Refresh the authenticated model.
        $resident->refresh();

        return redirect()
            ->route('resident.dashboard')
            ->with(
                'success',
                'Your password has been changed successfully.'
            );
    }
}