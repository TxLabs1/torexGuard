<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Threat extends Model
{
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'server_id', 'organization_id', 'threat_id',
        'type', 'severity', 'status', 'title', 'description',
        'file_path', 'file_hash', 'file_size', 'process_name', 'process_id',
        'source_ip', 'source_country', 'confidence_score',
        'detection_details', 'signatures_matched', 'auto_remediated',
        'remediation_action', 'detected_at', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'detection_details' => 'array',
        'signatures_matched' => 'array',
        'auto_remediated' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function logs()
    {
        return $this->hasMany(ThreatLog::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }

    public function quarantineFile()
    {
        return $this->hasOne(QuarantineFile::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'critical' => '#ff3366',
            'high' => '#ff6b35',
            'medium' => '#ffd700',
            'low' => '#00ff88',
            default => '#aaaaaa',
        };
    }

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            'critical' => 'badge-critical',
            'high' => 'badge-high',
            'medium' => 'badge-medium',
            'low' => 'badge-low',
            default => 'badge-secondary',
        };
    }

    public static function generateThreatId(): string
    {
        return 'THR-'.strtoupper(substr(md5(uniqid()), 0, 8));
    }
}
