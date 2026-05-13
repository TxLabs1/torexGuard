@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0" style="font-size:22px;color:var(--tg-text-primary);font-weight:700">Notifications</h1>

    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-sm btn-outline-light" type="submit">
            <i class="fas fa-check-double me-1"></i> Mark all as read
        </button>
    </form>
</div>

<div class="card" style="background:var(--tg-bg-card);border:1px solid var(--tg-border);border-radius:14px;padding:0;overflow:hidden">
    @if($notifications->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="fas fa-bell-slash mb-3" style="font-size:32px;opacity:0.4"></i>
            <div>No notifications yet.</div>
        </div>
    @else
        <ul class="list-group list-group-flush" style="background:transparent">
            @foreach($notifications as $n)
                @php
                    $data = $n->data;
                    $sev  = $data['severity'] ?? null;
                    $url  = $data['url'] ?? '#';
                    $isUnread = is_null($n->read_at);
                @endphp
                <li class="list-group-item d-flex align-items-start gap-3" style="background:{{ $isUnread ? 'rgba(255,255,255,0.02)' : 'transparent' }};border-color:var(--tg-border);color:var(--tg-text-primary);padding:14px 16px">
                    <span style="width:6px;height:6px;border-radius:50%;margin-top:7px;background:{{ $isUnread ? 'var(--tg-accent)' : 'transparent' }}"></span>
                    <div class="flex-grow-1">
                        <div style="font-weight:600">
                            @if(($data['type'] ?? '') === 'threat_detected')
                                <i class="fas fa-shield-virus me-1" style="color:var(--tg-critical)"></i>
                                Threat: {{ $data['title'] ?? 'detected' }}
                                @if($sev) <span class="badge ms-1" style="background:rgba(255,51,102,0.15);color:var(--tg-critical)">{{ strtoupper($sev) }}</span> @endif
                            @elseif(($data['type'] ?? '') === 'server_offline')
                                <i class="fas fa-server me-1" style="color:var(--tg-warning)"></i>
                                Server offline: {{ $data['hostname'] ?? 'unknown' }}
                            @elseif(($data['type'] ?? '') === 'weekly_digest')
                                <i class="fas fa-chart-line me-1" style="color:var(--tg-info)"></i>
                                Weekly digest ({{ $data['from'] ?? '' }} → {{ $data['to'] ?? '' }})
                            @else
                                <i class="fas fa-bell me-1"></i>
                                {{ $data['title'] ?? 'Notification' }}
                            @endif
                        </div>
                        <div class="text-muted" style="font-size:12px">{{ $n->created_at?->diffForHumans() }}</div>
                    </div>
                    <a href="{{ $url }}" class="btn btn-sm btn-outline-light">Open</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>

<div class="mt-3">
    {{ $notifications->links() }}
</div>
@endsection
