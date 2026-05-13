<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'project_id', 'threat_id', 'organization_id', 'title',
        'message', 'severity', 'channel', 'is_read', 'is_sent',
        'sent_at', 'read_at', 'meta',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'is_read' => 'boolean',
        'is_sent' => 'boolean',
        'meta' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function threat()
    {
        return $this->belongsTo(Threat::class);
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }
}
