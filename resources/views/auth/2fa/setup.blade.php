@extends('layouts.app')

@section('content')
<div class="tg-card" style="max-width:540px;margin:32px auto">
    <div class="tg-card-header">
        <i class="fas fa-shield-halved text-accent" style="font-size:14px"></i>
        <div class="tg-card-title">Enable Two-Factor Authentication</div>
    </div>
    <div class="tg-card-body">
        <p class="text-muted" style="margin-bottom:20px">
            Scan the QR code with an authenticator app (1Password, Authy, Google Authenticator)
            then enter the 6-digit code to confirm.
        </p>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:14px">{{ session('success') }}</div>
        @endif

        <div style="text-align:center;margin-bottom:20px">
            <img alt="2FA QR code"
                 src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($qrUrl) }}"
                 style="background:#fff;padding:10px;border-radius:12px">
        </div>

        <div class="tg-form-label">Manual entry secret</div>
        <div class="tg-form-control mono" style="font-size:13px;margin-bottom:18px">{{ chunk_split($secret, 4, ' ') }}</div>

        <form method="POST" action="{{ route('2fa.confirm') }}">
            @csrf
            <div class="tg-form-label">6-digit code</div>
            <input type="text" name="code" class="tg-form-control mono" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
            @error('code')<div class="text-danger mt-1" style="font-size:12px">{{ $message }}</div>@enderror

            <button type="submit" class="btn-tg-primary mt-3" style="width:100%">
                Verify &amp; activate
            </button>
        </form>
    </div>
</div>
@endsection
