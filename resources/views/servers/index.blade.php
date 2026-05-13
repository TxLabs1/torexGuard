{{-- resources/views/servers/index.blade.php --}}
@extends('layouts.app')
@section('title','Servers')
@section('page-title','Servers')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div style="font-size:13px;color:var(--tg-text-muted)">{{ $servers->total() }} server{{ $servers->total() !== 1 ? 's' : '' }} registered</div>
    <a href="{{ route('servers.create') }}" class="btn-tg-primary">
        <i class="fas fa-plus"></i> Add Server
    </a>
</div>

<div class="tg-card">
    <table class="tg-table">
        <thead>
            <tr><th>SERVER</th><th>PROJECT</th><th>STATUS</th><th>AGENT</th><th>CPU</th><th>MEM</th><th>DISK</th><th>THREATS</th><th>LAST SEEN</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($servers as $server)
            <tr>
                <td>
                    <a href="{{ route('servers.show', $server) }}" style="color:var(--tg-text-primary);font-weight:600;text-decoration:none;font-size:13px">
                        {{ $server->name }}
                    </a>
                    <div class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ $server->hostname }}</div>
                    @if($server->ip_address)
                    <div class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ $server->ip_address }}</div>
                    @endif
                </td>
                <td style="font-size:13px;color:var(--tg-text-secondary)">{{ $server->project->name ?? '—' }}</td>
                <td>
                    <span class="tg-status {{ $server->isOnline() ? 'online' : ($server->status === 'critical' ? 'critical' : 'offline') }}">
                        <div class="tg-status-dot"></div>
                        {{ ucfirst($server->status) }}
                    </span>
                </td>
                <td>
                    @if($server->agent)
                        <span class="tg-badge {{ $server->agent->status === 'active' ? 'badge-success' : 'badge-medium' }}" style="font-size:10px">
                            v{{ $server->agent->version }}
                        </span>
                    @else
                        <span class="tg-badge" style="background:rgba(255,255,255,0.04);color:var(--tg-text-muted);border:1px solid var(--tg-border);font-size:10px">
                            NOT INSTALLED
                        </span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="tg-progress" style="width:50px">
                            <div class="tg-progress-bar {{ $server->cpu_usage > 80 ? 'critical' : 'accent' }}"
                                 style="width:{{ $server->cpu_usage }}%"></div>
                        </div>
                        <span class="mono" style="font-size:12px;color:{{ $server->cpu_usage > 80 ? 'var(--tg-critical)' : 'var(--tg-text-secondary)' }}">
                            {{ $server->cpu_usage }}%
                        </span>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="tg-progress" style="width:50px">
                            <div class="tg-progress-bar {{ $server->memory_usage > 85 ? 'critical' : 'medium' }}"
                                 style="width:{{ $server->memory_usage }}%"></div>
                        </div>
                        <span class="mono" style="font-size:12px">{{ $server->memory_usage }}%</span>
                    </div>
                </td>
                <td>
                    <span class="mono" style="font-size:12px;color:{{ $server->disk_usage > 90 ? 'var(--tg-critical)' : 'var(--tg-text-secondary)' }}">
                        {{ $server->disk_usage }}%
                    </span>
                </td>
                <td>
                    <span style="font-weight:600;color:{{ $server->threats->count() > 0 ? 'var(--tg-critical)' : 'var(--tg-accent)' }}">
                        {{ $server->threats->count() }}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--tg-text-muted)">
                    {{ $server->last_heartbeat_at?->diffForHumans() ?? 'Never' }}
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('servers.show', $server) }}" class="btn-tg-ghost" style="padding:5px 10px;font-size:11px">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button onclick="removeServer({{ $server->id }})" class="btn-tg-danger" style="padding:5px 10px;font-size:11px">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="10">
                <div class="text-center py-5">
                    <i class="fas fa-server" style="font-size:48px;color:var(--tg-accent);opacity:0.25;display:block;margin-bottom:16px"></i>
                    <div style="font-family:var(--tg-font-display);font-size:18px;margin-bottom:6px">No servers connected</div>
                    <div style="color:var(--tg-text-muted);font-size:13px;margin-bottom:20px">Install the Torex Guard agent on your server to start monitoring</div>
                    <a href="{{ route('servers.create') }}" class="btn-tg-primary"><i class="fas fa-plus"></i> Add Server</a>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($servers->hasPages())
    <div class="tg-card-body" style="border-top:1px solid var(--tg-border)">
        {{ $servers->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function removeServer(id) {
    if (!confirm('Remove this server? All associated data will be deleted.')) return;
    const r = await fetch(`/servers/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
    if (r.ok) { showToast('Removed', 'Server removed successfully', 'success'); setTimeout(() => location.reload(), 1200); }
}
</script>
@endpush
