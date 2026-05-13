<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use Auditable, HasFactory;

    protected $hidden = ['token_hash'];

    protected $fillable = [
        'server_id', 'project_id', 'agent_id', 'version',
        'token_hash', 'token_prefix',
        'status', 'connected_at', 'disconnected_at',
        'last_heartbeat_at', 'capabilities', 'config',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'capabilities' => 'array',
        'config' => 'array',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a fresh plaintext token. NEVER persist this directly.
     */
    public static function generateToken(): string
    {
        return 'agent_'.bin2hex(random_bytes(32));
    }

    /**
     * Rotate the agent's token, storing only the hash. Returns the
     * plaintext value so the caller can hand it to the agent ONCE.
     */
    public function issueToken(): string
    {
        $token = self::generateToken();

        $this->forceFill([
            'token_hash' => hash('sha256', $token),
            'token_prefix' => mb_substr($token, 0, 14),
        ])->save();

        return $token;
    }

    public static function findByToken(string $plaintext): ?self
    {
        return self::query()
            ->where('token_hash', hash('sha256', $plaintext))
            ->first();
    }
}
