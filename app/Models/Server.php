<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'organization_id', 'name', 'hostname',
        'ip_address', 'public_ip', 'os', 'os_version', 'arch',
        'cpu_count', 'memory_total', 'disk_total', 'status',
        'cpu_usage', 'memory_usage', 'disk_usage',
        'last_heartbeat_at', 'meta',
    ];

    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'disk_usage' => 'float',
        'meta' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function threats()
    {
        return $this->hasMany(Threat::class);
    }

    public function events()
    {
        return $this->hasMany(ThreatEvent::class);
    }

    public function fileSnapshots()
    {
        return $this->hasMany(FileSnapshot::class);
    }

    public function scanResults()
    {
        return $this->hasMany(ScanResult::class);
    }

    public function isOnline(): bool
    {
        if (! $this->last_heartbeat_at) {
            return false;
        }

        return $this->last_heartbeat_at->diffInSeconds(now()) < 120;
    }

    public function memoryTotalHuman(): string
    {
        return round($this->memory_total / 1073741824, 1).' GB';
    }

    public function diskTotalHuman(): string
    {
        return round($this->disk_total / 1073741824, 1).' GB';
    }
}
