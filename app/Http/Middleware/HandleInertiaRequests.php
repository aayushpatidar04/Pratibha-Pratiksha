<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = Auth::guard('web')->user();
        $resident = Auth::guard('resident')->user();
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ?? null,
                'permissions' => $user ? $this->resolvePermissions($user) : [],
                'resident' => $resident ?? null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'bulk_upload_summary' => fn() =>
                    $request->session()->get('bulk_upload_summary'),

                'bulk_upload_failures' => fn() =>
                    $request->session()->get('bulk_upload_failures'),
            ],
        ];
    }

    protected function resolvePermissions($user): array
    {
        $resolved = [];

        foreach (config('modules.modules') as $module) {
            $resolved[$module['key']] = $user->hasFullAccess()
                ? $module['actions']
                : array_values(array_intersect($module['actions'], $user->permissions[$module['key']] ?? []));
        }

        return $resolved;
    }
}
