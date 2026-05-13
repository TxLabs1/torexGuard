<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_in_with_correct_credentials_when_2fa_disabled(): void
    {
        $user = User::factory()->create([
            'email' => 'alice@example.com',
            'password' => Hash::make('correct-horse-battery'),
        ]);

        $this->post('/login', [
            'email' => 'alice@example.com',
            'password' => 'correct-horse-battery',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_2fa_must_satisfy_challenge(): void
    {
        /** @var TwoFactorService $tfa */
        $tfa = app(TwoFactorService::class);
        $user = User::factory()->create([
            'email' => 'bob@example.com',
            'password' => Hash::make('password123'),
        ]);

        $secret = $tfa->generateSecret($user);
        $tfa->confirm($user->fresh());

        $this->post('/login', [
            'email' => 'bob@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('login.2fa.challenge'));

        $this->assertGuest();

        $code = (new Google2FA)->getCurrentOtp($secret);

        $this->post(route('login.2fa.verify'), [
            'code' => $code,
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_login_throttles_after_too_many_failures(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'nope@example.com', 'password' => 'x']);
        }

        $this->post('/login', ['email' => 'nope@example.com', 'password' => 'x'])
            ->assertStatus(429);
    }
}
