@extends('layouts.guest', ['title' => 'Two-Factor Authentication'])

@section('content')
<div class="login-card" style="max-width:420px;margin:40px auto;padding:32px;background:var(--tg-card,#0d1526);border:1px solid var(--tg-border,rgba(0,255,136,0.08));border-radius:16px">
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:28px;font-weight:700;letter-spacing:1px;margin-bottom:8px;color:var(--tg-text,#e8f0fe)">
        Two-Factor Required
    </h1>
    <p style="color:var(--tg-muted,#4a6080);margin-bottom:24px;font-size:14px">
        Enter the 6-digit code from your authenticator app, or use a one-time recovery code.
    </p>

    @if($errors->any())
        <div class="alert alert-danger" style="background:rgba(255,51,102,0.15);border:1px solid #ff3366;color:#ff3366;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.2fa.verify') }}">
        @csrf

        <div style="margin-bottom:16px">
            <label style="display:block;font-size:12px;color:var(--tg-muted,#4a6080);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px">
                Authenticator code
            </label>
            <input type="text" name="code" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                   style="width:100%;padding:12px 14px;background:#040810;border:1px solid var(--tg-border,rgba(0,255,136,0.15));color:var(--tg-text,#e8f0fe);border-radius:8px;font-family:'JetBrains Mono',monospace;letter-spacing:4px;font-size:16px;text-align:center"
                   autofocus>
        </div>

        <details style="margin-bottom:18px">
            <summary style="cursor:pointer;color:var(--tg-muted,#4a6080);font-size:13px">Lost your phone? Use a recovery code</summary>
            <input type="text" name="recovery_code"
                   placeholder="recovery-code"
                   style="width:100%;margin-top:8px;padding:10px 12px;background:#040810;border:1px solid var(--tg-border,rgba(0,255,136,0.15));color:var(--tg-text,#e8f0fe);border-radius:8px;font-family:'JetBrains Mono',monospace;font-size:13px">
        </details>

        <button type="submit"
                style="width:100%;padding:12px 16px;background:var(--tg-accent,#00ff88);color:#000;border:none;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer">
            Verify
        </button>
    </form>
</div>
@endsection
