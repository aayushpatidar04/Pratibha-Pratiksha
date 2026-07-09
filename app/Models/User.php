<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'role',
        'is_active',
        'last_sign_in_at',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_sign_in_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    /**
     * Roles that always bypass the per-module permission matrix.
     */
    public function hasFullAccess(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check whether this user can perform $action (view/create/edit/delete) on $module.
     * super_admin always passes. Other roles fall back to their explicit permissions
     * array (set from Admin > Users > Manage Permissions); no entry means no access.
     */
    public function hasPermission(string $module, string $action = 'view'): bool
    {
        if ($this->hasFullAccess()) {
            return true;
        }

        $modulePermissions = $this->permissions[$module] ?? [];

        return in_array($action, $modulePermissions, true);
    }

    /**
     * Default permission set granted to a brand-new non-super-admin account: view-only
     * everywhere, so they can see the app immediately but a super admin has to
     * deliberately grant create/edit/delete access module by module.
     */
    public static function defaultPermissions(): array
    {
        $permissions = [];
        foreach (config('modules.modules') as $module) {
            // The Admin/User-management module itself is never granted by default —
            // only super_admin (which bypasses this map entirely) can reach it unless
            // a super admin explicitly opts a user into it later.
            if (in_array($module['key'], ['admin_users', 'kyc_settings'], true)) {
                $permissions[$module['key']] = [];
                continue;
            }

            $permissions[$module['key']] = ['view'];
        }

        return $permissions;
    }
}