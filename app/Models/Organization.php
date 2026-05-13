<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'logo', 'website', 'email',
        'plan', 'plan_expires_at', 'max_servers', 'max_projects',
        'is_active', 'settings',
    ];

    protected $casts = [
        'plan_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function threats()
    {
        return $this->hasMany(Threat::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function notificationSettings()
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function notificationSetting()
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function activeThreatCount(): int
    {
        return $this->threats()->where('status', 'active')->count();
    }

    public function overallRiskScore(): int
    {
        return (int) ($this->projects()->avg('risk_score') ?? 0);
    }
}
