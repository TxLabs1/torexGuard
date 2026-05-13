@extends('layouts.app')

@section('content')
<div class="tg-card" style="max-width:560px;margin:32px auto">
    <div class="tg-card-header">
        <i class="fas fa-key text-accent" style="font-size:14px"></i>
        <div class="tg-card-title">Save your recovery codes</div>
    </div>
    <div class="tg-card-body">
        <div class="alert alert-warning" style="background:#3a2a00;border:1px solid #b58900;color:#ffd966;padding:12px 16px;border-radius:8px;margin-bottom:18px">
            <strong>Store these somewhere safe.</strong> Each code can be used once if you lose access to your authenticator. They will not be shown again.
        </div>

        <div class="mono" style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:18px;line-height:2">
            @foreach($recoveryCodes as $code)
                <div>{{ $code }}</div>
            @endforeach
        </div>

        <a href="{{ route('settings.profile') }}" class="btn-tg-primary mt-3" style="display:inline-block">
            I've saved them
        </a>
    </div>
</div>
@endsection
