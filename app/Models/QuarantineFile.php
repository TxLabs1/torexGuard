<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarantineFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'threat_id', 'project_id', 'server_id', 'original_path',
        'quarantine_path', 'file_hash', 'file_size', 'status',
        'quarantined_by', 'quarantined_at', 'restored_at',
    ];

    protected $casts = [
        'quarantined_at' => 'datetime',
        'restored_at' => 'datetime',
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

    public function quarantinedBy()
    {
        return $this->belongsTo(User::class, 'quarantined_by');
    }
}
