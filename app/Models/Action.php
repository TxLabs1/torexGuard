<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'threat_id', 'project_id', 'server_id', 'triggered_by', 'type',
        'status', 'is_automated', 'target', 'result', 'error',
        'executed_at', 'dispatched_at',
    ];

    protected $casts = [
        'executed_at'   => 'datetime',
        'dispatched_at' => 'datetime',
        'is_automated'  => 'boolean',
    ];

    public function threat()
    {
        return $this->belongsTo(Threat::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
