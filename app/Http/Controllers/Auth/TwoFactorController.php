<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $tfa) {}

    /** Show enrollment screen: QR + manual entry secret + verify form. */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($this->tfa->isEnabled($user)) {
            return redirect()->route('settings.profile')
                ->with('success', 'Two-factor authentication is already enabled.');
        }

        $secret = $user->two_factor_secret
            ? Crypt::decryptString($user->two_factor_secret)
            : $this->tfa->generateSecret($user);

        $qrUrl = $this->tfa->qrCodeUrl($user, $secret);

        return view('auth.2fa.setup', compact('secret', 'qrUrl'));
    }

    /** Verify enrollment + activate 2FA. Returns the recovery codes once. */
    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $user = $request->user();

        if (! $this->tfa->verify($user, $data['code'])) {
            return back()->withErrors(['code' => 'Invalid code. Try again.']);
        }

        $recoveryCodes = $this->tfa->confirm($user);

        return view('auth.2fa.recovery-codes', compact('recoveryCodes'));
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (! \Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Password does not match.']);
        }

        $this->tfa->disable($user);

        return redirect()->route('settings.profile')->with('success', 'Two-factor authentication disabled.');
    }

    /** Login step 2: prompt the user to enter their TOTP after password succeeded. */
    public function challenge(Request $request)
    {
        if (! $request->session()->has('login.2fa.user_id')) {
            return redirect()->route('login');
        }
        return view('auth.2fa.challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $data = $request->validate([
            'code'          => 'nullable|string|max:10',
            'recovery_code' => 'nullable|string|max:32',
        ]);

        if (! $userId = $request->session()->get('login.2fa.user_id')) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::findOrFail($userId);

        $verified = false;
        if (! empty($data['code'])) {
            $verified = $this->tfa->verify($user, $data['code']);
        }
        if (! $verified && ! empty($data['recovery_code'])) {
            $verified = $this->tfa->consumeRecoveryCode($user, $data['recovery_code']);
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'Invalid 2FA code or recovery token.']);
        }

        Auth::login($user, $request->session()->get('login.2fa.remember', false));
        $request->session()->forget(['login.2fa.user_id', 'login.2fa.remember']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
