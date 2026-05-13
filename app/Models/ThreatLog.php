<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreatLog extends Model
{
    use HasFactory;

    protected $fillable = ['threat_id', 'user_id', 'action', 'note', 'meta'];

    protected $casts = ['meta' => 'array'];

    public function threat()
    {
        return $this->belongsTo(Threat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
