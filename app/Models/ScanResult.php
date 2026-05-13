<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'server_id', 'scan_id', 'type', 'status',
        'files_scanned', 'threats_found', 'files_clean',
        'duration_seconds', 'summary', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'summary' => 'array',
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
