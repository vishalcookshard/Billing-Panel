<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Mass-assignment protection: never allow sensitive fields to be assigned
    protected $guarded = [
        'id',
        'is_admin',
        'role',
        'roles',
        'permissions',
        'email_verified_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_admin' => 'boolean',
        'email_verified_at' => 'datetime',
        'password_change_required' => 'boolean',
    ];

    /**
     * Get all orders for this user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Roles and permissions
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')->with('permissions');
    }

    public function hasRole(string $role): bool
    {
        if ($this->is_admin) return true;
        // Eager loaded roles
        return $this->roles->contains('name', $role);
    }

    public function cachedPermissions()
    {
        return cache()->rememberForever('user_permissions_' . $this->id, function () {
            return $this->roles->flatMap(function ($role) {
                return $role->permissions->pluck('name');
            })->unique()->toArray();
        });
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) return true;
        return in_array($permission, $this->cachedPermissions(), true);
    }
}
