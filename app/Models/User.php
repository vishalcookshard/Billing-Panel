<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'google_id',
        'discord_id',
        'github_id',
        'avatar_url',
        'password_change_required',
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
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $role): bool
    {
        if ($this->is_admin) return true;
        return $this->roles()->where('name', $role)->exists();
    }

    public function permissions()
    {
        return \App\Models\Permission::whereHas('roles', function ($q) { $q->whereIn('roles.id', $this->roles->pluck('id')->toArray()); });
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->is_admin) return true;

        return \App\Models\Permission::where('name', $permission)
            ->whereHas('roles', function ($q) { $q->whereIn('roles.id', $this->roles->pluck('id')->toArray()); })
            ->exists();
    }
}
