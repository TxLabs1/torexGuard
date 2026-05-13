{{-- resources/views/settings/api-tokens.blade.php --}}
@extends('layouts.app')
@section('title','API Tokens')
@section('page-title','Settings')

@section('content')
<div class="row g-3">
    <div class="col-lg-3">
        <div class="tg-card">
            <div class="tg-card-body" style="padding:12px">
                @foreach([
                    ['route'=>'settings.profile','icon'=>'fa-user','label'=>'Profile'],
                    ['route'=>'settings.organization','icon'=>'fa-building','label'=>'Organization'],
                    ['route'=>'settings.notifications','icon'=>'fa-bell','label'=>'Notifications'],
                    ['route'=>'settings.api-tokens','icon'=>'fa-key','label'=>'API Tokens'],
                ] as $item)
                <a href="{{ route($item['route']) }}"
                   class="tg-nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                   style="border-radius:10px;margin-bottom:2px">
                    <span class="nav-icon"><i class="fas {{ $item['icon'] }}"></i></span>
                    {{ $item['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        {{-- New token reveal --}}
        @if(session('new_token'))
        <div style="background:var(--tg-accent-dim);border:1px solid rgba(0,255,136,0.25);border-radius:14px;padding:20px;margin-bottom:20px">
            <div style="font-weight:700;color:var(--tg-accent);margin-bottom:10px;font-size:15px">
                <i class="fas fa-key me-2"></i>Token Created — Copy it now!
            </div>
            <div style="font-size:12px;color:var(--tg-text-muted);margin-bottom:10px">This token will not be shown again.</div>
            <div class="d-flex gap-2">
                <div class="tg-form-control mono" style="flex:1;font-size:12px;word-break:break-all">{{ session('new_token') }}</div>
                <button class="btn-tg-primary" onclick="navigator.clipboard.writeText('{{ session('new_token') }}').then(()=>showToast('Copied','Token copied to clipboard','success'))">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        @endif

        {{-- Create token --}}
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-plus text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Create API Token</div>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('settings.api-tokens.create') }}">
                    @csrf
                    <div class="d-flex gap-2">
                        <input type="text" name="name" class="tg-form-control" placeholder="Token name (e.g. CI/CD Pipeline, Monitoring Script)" required>
                        <button type="submit" class="btn-tg-primary" style="white-space:nowrap">
                            <i class="fas fa-key"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Token list --}}
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-key text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Active Tokens</div>
                <span class="tg-badge badge-success">{{ $tokens->where('is_active',true)->count() }} active</span>
            </div>
            <table class="tg-table">
                <thead><tr><th>NAME</th><th>TOKEN PREFIX</th><th>CREATED</th><th>LAST USED</th><th></th></tr></thead>
                <tbody>
                    @forelse($tokens as $token)
                    <tr style="{{ !$token->is_active ? 'opacity:0.5' : '' }}">
                        <td style="font-weight:600;font-size:13px;color:var(--tg-text-primary)">{{ $token->name }}</td>
                        <td class="mono" style="font-size:12px;color:var(--tg-text-muted)">{{ $token->maskedToken() }}</td>
                        <td style="font-size:12px;color:var(--tg-text-muted)">{{ $token->created_at->format('Y-m-d') }}</td>
                        <td style="font-size:12px;color:var(--tg-text-muted)">{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                        <td>
                            @if($token->is_active)
                            <form method="POST" action="{{ route('settings.api-tokens.revoke', $token) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-tg-danger" style="padding:4px 10px;font-size:11px"
                                        onclick="return confirm('Revoke this token?')">
                                    <i class="fas fa-ban"></i> Revoke
                                </button>
                            </form>
                            @else
                            <span class="tg-badge badge-critical" style="font-size:10px">REVOKED</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5">
                        <div class="text-center py-4" style="color:var(--tg-text-muted);font-size:13px">No tokens created yet</div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
