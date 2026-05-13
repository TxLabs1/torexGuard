<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'server_id', 'file_path', 'file_hash',
        'file_size', 'permissions', 'owner', 'file_modified_at',
        'is_baseline', 'change_type', 'flagged',
    ];

    protected $casts = [
        'file_modified_at' => 'datetime',
        'is_baseline' => 'boolean',
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

    public function fileSizeHuman(): string
    {
        $size = $this->file_size;
        if ($size < 1024) return $size . ' B';
        if ($size < 1048576) return round($size / 1024, 1) . ' KB';
        return round($size / 1048576, 1) . ' MB';
    }
}
