<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $hidden = ['token_hash'];

    protected $fillable = [
        'user_id', 'organization_id', 'name',
        'token_hash', 'token_prefix',
        'abilities', 'last_used_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'abilities'    => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public static function generateToken(): string
    {
        return 'tgt_'.bin2hex(random_bytes(32));
    }

    public function issueToken(): string
    {
        $token = self::generateToken();
        $this->forceFill([
            'token_hash'   => hash('sha256', $token),
            'token_prefix' => mb_substr($token, 0, 12),
        ])->save();

        return $token;
    }

    public static function findByToken(string $plaintext): ?self
    {
        return self::query()
            ->where('token_hash', hash('sha256', $plaintext))
            ->first();
    }

    public function maskedToken(): string
    {
        return ($this->token_prefix ?? 'tgt_').str_repeat('*', 30);
    }
}
