{{-- resources/views/alerts/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Alerts')
@section('page-title', 'Alert Center')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div style="font-size:13px;color:var(--tg-text-muted)">
        <span class="text-critical">{{ $unreadCount }}</span> unread alerts
    </div>
    @if($unreadCount > 0)
    <form method="POST" action="{{ route('alerts.read-all') }}">
        @csrf
        <button type="submit" class="btn-tg-ghost">
            <i class="fas fa-check-double"></i> Mark All Read
        </button>
    </form>
    @endif
</div>

<div class="tg-card">
    <table class="tg-table">
        <thead>
            <tr><th>SEVERITY</th><th>TITLE</th><th>PROJECT</th><th>CHANNEL</th><th>TIME</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($alerts as $alert)
            <tr style="{{ !$alert->is_read ? 'background:rgba(0,255,136,0.02)' : '' }}">
                <td><span class="tg-badge badge-{{ $alert->severity }}">{{ strtoupper($alert->severity) }}</span></td>
                <td>
                    <div style="font-weight:{{ !$alert->is_read ? '600' : '400' }};color:var(--tg-text-primary);font-size:13px">
                        {{ !$alert->is_read ? '● ' : '' }}{{ $alert->title }}
                    </div>
                    <div style="font-size:12px;color:var(--tg-text-muted)">{{ Str::limit($alert->message, 80) }}</div>
                </td>
                <td style="font-size:13px">{{ $alert->project->name ?? '—' }}</td>
                <td><span class="mono" style="font-size:12px;color:var(--tg-text-muted)">{{ strtoupper($alert->channel) }}</span></td>
                <td style="font-size:12px;color:var(--tg-text-muted)">{{ $alert->created_at->diffForHumans() }}</td>
                <td>
                    @if(!$alert->is_read)
                    <button onclick="markRead({{ $alert->id }}, this)" class="btn-tg-ghost" style="padding:4px 10px;font-size:11px">
                        <i class="fas fa-check"></i>
                    </button>
                    @endif
                    @if($alert->threat_id)
                    <a href="{{ route('threats.show', $alert->threat_id) }}" class="btn-tg-ghost" style="padding:4px 10px;font-size:11px">
                        <i class="fas fa-eye"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash" style="font-size:40px;color:var(--tg-accent);opacity:0.3;display:block;margin-bottom:12px"></i>
                    <div style="color:var(--tg-text-muted)">No alerts — all quiet</div>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($alerts->hasPages())
    <div class="tg-card-body" style="border-top:1px solid var(--tg-border)">
        {{ $alerts->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function markRead(alertId, btn) {
    await fetch(`/alerts/${alertId}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } });
    btn.closest('tr').style.background = '';
    btn.remove();
}
</script>
@endpush
