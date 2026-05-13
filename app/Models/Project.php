<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use Auditable, BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'user_id', 'name', 'slug', 'description',
        'type', 'environment',
        'api_key_hash', 'api_key_prefix', 'api_secret_hash',
        'risk_score', 'status',
        'last_scan_at', 'last_event_at', 'settings',
    ];

    protected $hidden = ['api_key_hash', 'api_secret_hash'];

    protected $casts = [
        'last_scan_at' => 'datetime',
        'last_event_at' => 'datetime',
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function threats()
    {
        return $this->hasMany(Threat::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function events()
    {
        return $this->hasMany(ThreatEvent::class);
    }

    public function scanResults()
    {
        return $this->hasMany(ScanResult::class);
    }

    public function fileSnapshots()
    {
        return $this->hasMany(FileSnapshot::class);
    }

    public function criticalThreats()
    {
        return $this->threats()->where('severity', 'critical')->where('status', 'active');
    }

    /**
     * Generate a new plaintext API key. NEVER persist this value directly —
     * use {@see issueApiKey()} which hashes before saving.
     */
    public static function generateApiKey(): string
    {
        return 'tg_'.bin2hex(random_bytes(24));
    }

    public static function generateApiSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a fresh key+secret pair, store hashes on the model, and
     * return the plaintext values so they can be shown to the user ONCE.
     *
     * @return array{api_key:string, api_secret:string}
     */
    public function issueApiKey(): array
    {
        $key = self::generateApiKey();
        $secret = self::generateApiSecret();

        $this->forceFill([
            'api_key_hash' => hash('sha256', $key),
            'api_key_prefix' => mb_substr($key, 0, 11),
            'api_secret_hash' => hash('sha256', $secret),
        ])->save();

        return ['api_key' => $key, 'api_secret' => $secret];
    }

    public static function findByApiKey(string $plaintext): ?self
    {
        return self::query()
            ->where('api_key_hash', hash('sha256', $plaintext))
            ->first();
    }

    /**
     * Display value for dashboard tables. Only the prefix is recoverable
     * once the plaintext has been shown to the user.
     */
    public function maskedApiKey(): string
    {
        return ($this->api_key_prefix ?? 'tg_').str_repeat('*', 32);
    }
}
