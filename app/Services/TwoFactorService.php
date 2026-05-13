<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-Factor authentication helper.
 *
 * - Secrets are stored encrypted in `users.two_factor_secret`.
 * - Recovery codes are bcrypt-hashed in `users.two_factor_recovery_codes`
 *   so even a DB leak doesn't grant skip-2FA-once tokens.
 * - 2FA is only considered "enabled" once the user has confirmed a TOTP code
 *   (two_factor_confirmed_at). Half-enrolled state can be cancelled without
 *   affecting login.
 */
class TwoFactorService
{
    public function __construct(
        private Google2FA $google2fa,
        private Encrypter $encrypter,
    ) {
        $this->google2fa->setWindow(2); // tolerate +/- one 30s window
    }

    /** Begin enrollment: generate + store an encrypted secret, return plaintext for QR. */
    public function generateSecret(User $user): string
    {
        $plain = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret'       => $this->encrypter->encryptString($plain),
            'two_factor_enabled'      => false,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $plain;
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Torex Guard'),
            $user->email,
            $secret,
        );
    }

    /** Verify a code against the secret currently stored on the user. */
    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }
        $secret = $this->encrypter->decryptString($user->two_factor_secret);

        return (bool) $this->google2fa->verifyKey($secret, str_replace(' ', '', $code));
    }

    /** Mark 2FA confirmed and (re)generate 8 single-use recovery codes. */
    public function confirm(User $user): array
    {
        $codes = collect(range(1, 8))->map(fn () => Str::lower(Str::random(10)))->all();

        $user->forceFill([
            'two_factor_enabled'        => true,
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => array_map(fn ($c) => password_hash($c, PASSWORD_BCRYPT), $codes),
        ])->save();

        return $codes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_enabled'        => false,
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /**
     * Burn a recovery code (returns true if it matched + was consumed).
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $hashes = $user->two_factor_recovery_codes ?? [];
        $code   = trim(Str::lower($code));

        foreach ($hashes as $idx => $hash) {
            if (password_verify($code, $hash)) {
                unset($hashes[$idx]);
                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($hashes),
                ])->save();
                return true;
            }
        }
        return false;
    }

    public function isEnabled(User $user): bool
    {
        return (bool) $user->two_factor_enabled && (bool) $user->two_factor_confirmed_at;
    }

    /** Convenience constructor for use outside the container. */
    public static function make(): self
    {
        return new self(new Google2FA, Crypt::getFacadeRoot());
    }
}
