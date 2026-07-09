<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'role', 'phone', 'is_active', 'last_sign_in_at', 'created_at', 'permissions']),
            'modules' => config('modules.modules'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,hostel_admin,warden,accountant,caretaker,staff',
            'phone' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        // Fresh non-super-admin accounts start view-only everywhere; a super admin
        // grants create/edit/delete access module by module from Manage Permissions.
        $validated['permissions'] = $validated['role'] === 'super_admin' ? null : User::defaultPermissions();

        User::create($validated);

        return back()->with('success', 'Staff account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:super_admin,hostel_admin,warden,accountant,caretaker,staff',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        // Would this update take away super-admin-ness (by role change or deactivation)
        // from someone who currently holds it?
        $losingSuperAdmin = $user->role === 'super_admin' && (
            (array_key_exists('role', $validated) && $validated['role'] !== 'super_admin')
            || (array_key_exists('is_active', $validated) && $validated['is_active'] === false)
        );

        if ($losingSuperAdmin) {
            $otherActiveSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return back()->with('error', 'This is the last active Super Admin — promote another user to Super Admin before changing this.');
            }
        }

        // Demoting away from super_admin: they previously had `permissions = null`
        // (meaningless for super_admin, since it bypasses the check entirely), so give
        // them the standard view-only defaults instead of silently leaving them with
        // zero access to every module.
        if (array_key_exists('role', $validated) && $user->role === 'super_admin' && $validated['role'] !== 'super_admin') {
            $validated['permissions'] = User::defaultPermissions();
        }

        // Promoting someone TO super_admin: their permissions map becomes irrelevant
        // (super_admin bypasses it), so clear it for clarity.
        if (array_key_exists('role', $validated) && $validated['role'] === 'super_admin') {
            $validated['permissions'] = null;
        }

        $user->update($validated);

        // If you just took away your own super_admin (and therefore your own access to
        // this Admin/User-management screen), `back()` would immediately 403 you on the
        // next page load since the permission middleware re-checks on every request.
        // Send yourself somewhere you can still reach instead.
        if ($losingSuperAdmin && $user->id === $request->user()->id) {
            return redirect()->route('dashboard')->with('success', 'Your role was updated. You no longer have access to User Management.');
        }

        return back()->with('success', 'User updated successfully.');
    }

    /**
     * Save the full permission matrix for one user (Admin > Users > Manage Permissions).
     * Expects: { permissions: { residents: ['view','create'], billing: ['view'], ... } }
     * Only super admins may reach this route (enforced via the 'permission' middleware
     * on the route + the UI only exposing the button to super admins).
     */
    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        if ($user->role === 'super_admin') {
            return back()->with('error', 'Super admins always have full access; permissions cannot be restricted for them.');
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'array',
            'permissions.*.*' => 'string|in:view,create,edit,delete',
        ]);

        $allowedModules = collect(config('modules.modules'))->pluck('key');

        // Strip out any module keys or actions that aren't part of the registry / not
        // valid for that specific module, so a tampered request can't grant bogus access.
        $clean = collect($validated['permissions'])
            ->only($allowedModules)
            ->map(function ($actions, $moduleKey) {
                $module = collect(config('modules.modules'))->firstWhere('key', $moduleKey);
                return array_values(array_intersect($actions, $module['actions'] ?? []));
            })
            ->toArray();

        $user->update(['permissions' => $clean]);

        return back()->with('success', "Permissions updated for {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', "You can't delete your own account.");
        }

        if ($user->role === 'super_admin') {
            $otherActiveSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                return back()->with('error', "You can't delete the last active Super Admin.");
            }
        }

        $user->delete();

        return back()->with('success', 'User removed.');
    }
}