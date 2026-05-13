<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreatEvent extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'project_id', 'server_id', 'event_id', 'type',
        'severity', 'payload', 'processed', 'flagged', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => 'array',
        'processed' => 'boolean',
        'flagged' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
