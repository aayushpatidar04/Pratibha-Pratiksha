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

    public static function availableModules(): array
    {
        return config('modules.modules', []);
    }

    public static function defaultPermissions(): array
    {
        return collect(
            static::availableModules()
        )
            ->mapWithKeys(
                fn (array $module): array => [
                    $module['key'] => [],
                ]
            )
            ->all();
    }

    public function hasPermission(
        string $module,
        string $action = 'view'
    ): bool {
        if ($this->role === 'super_admin') {
            return true;
        }

        $permissions = $this->permissions ?? [];

        return in_array(
            $action,
            $permissions[$module] ?? [],
            true
        );
    }

    public function hasAnyPermission(
        string $module,
        array $actions
    ): bool {
        if ($this->role === 'super_admin') {
            return true;
        }

        foreach ($actions as $action) {
            if ($this->hasPermission($module, $action)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(
        string $module,
        array $actions
    ): bool {
        if ($this->role === 'super_admin') {
            return true;
        }

        foreach ($actions as $action) {
            if (!$this->hasPermission($module, $action)) {
                return false;
            }
        }

        return true;
    }
}