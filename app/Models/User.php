<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Auditable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'timezone',
        'role', 'last_login_at', 'last_login_ip', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'email_verified_at'         => 'datetime',
        'last_login_at'             => 'datetime',
        'two_factor_enabled'        => 'boolean',
        'two_factor_confirmed_at'   => 'datetime',
        'two_factor_recovery_codes' => 'array',
        'is_active'                 => 'boolean',
        'password'                  => 'hashed',
    ];

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin') || $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['superadmin', 'admin'])
            || in_array($this->role, ['superadmin', 'admin'], true);
    }
}
