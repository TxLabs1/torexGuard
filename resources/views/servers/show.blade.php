{{-- resources/views/servers/show.blade.php --}}
@extends('layouts.app')
@section('title', $server->name)
@section('page-title', $server->name)

@section('content')
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('servers.index') }}" class="btn-tg-ghost" style="padding:7px 14px;font-size:13px">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <span class="tg-status {{ $server->isOnline() ? 'online' : 'offline' }}">
        <div class="tg-status-dot"></div>
        {{ $server->isOnline() ? 'Online' : 'Offline' }}
    </span>
    @if($server->agent)
    <span class="tg-badge badge-success" style="font-size:11px">Agent v{{ $server->agent->version }}</span>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="tg-stat-card accent">
            <div class="tg-stat-label">CPU Usage</div>
            <div class="tg-stat-value accent" id="cpuVal">{{ $server->cpu_usage }}%</div>
            <div class="tg-progress mt-2"><div class="tg-progress-bar accent" id="cpuBar" style="width:{{ $server->cpu_usage }}%"></div></div>
            <div class="tg-stat-icon"><i class="fas fa-microchip"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tg-stat-card medium">
            <div class="tg-stat-label">Memory Usage</div>
            <div class="tg-stat-value medium" id="memVal">{{ $server->memory_usage }}%</div>
            <div class="tg-progress mt-2"><div class="tg-progress-bar medium" id="memBar" style="width:{{ $server->memory_usage }}%"></div></div>
            <div class="tg-stat-icon"><i class="fas fa-memory"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tg-stat-card high">
            <div class="tg-stat-label">Disk Usage</div>
            <div class="tg-stat-value high" id="diskVal">{{ $server->disk_usage }}%</div>
            <div class="tg-progress mt-2"><div class="tg-progress-bar {{ $server->disk_usage > 85 ? 'critical' : 'medium' }}" id="diskBar" style="width:{{ $server->disk_usage }}%"></div></div>
            <div class="tg-stat-icon"><i class="fas fa-hard-drive"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tg-stat-card critical">
            <div class="tg-stat-label">Active Threats</div>
            <div class="tg-stat-value critical">{{ $server->threats->where('status','active')->count() }}</div>
            <div class="tg-stat-sub">{{ $server->threats->count() }} total detected</div>
            <div class="tg-stat-icon"><i class="fas fa-bug"></i></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-server text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">System Info</div>
            </div>
            <div class="tg-card-body">
                @foreach([
                    ['label'=>'Hostname','value'=>$server->hostname],
                    ['label'=>'IP Address','value'=>$server->ip_address ?? '—'],
                    ['label'=>'Public IP','value'=>$server->public_ip ?? '—'],
                    ['label'=>'OS','value'=>($server->os ?? '—') . ' ' . ($server->os_version ?? '')],
                    ['label'=>'Architecture','value'=>$server->arch ?? '—'],
                    ['label'=>'CPU Cores','value'=>$server->cpu_count ?? '—'],
                    ['label'=>'Total RAM','value'=>$server->memoryTotalHuman()],
                    ['label'=>'Total Disk','value'=>$server->diskTotalHuman()],
                ] as $info)
                <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
                    <span style="font-size:12px;color:var(--tg-text-muted)">{{ $info['label'] }}</span>
                    <span class="mono" style="font-size:12px;color:var(--tg-text-secondary)">{{ $info['value'] }}</span>
                </div>
                @endforeach
                <div style="display:flex;justify-content:space-between;padding:9px 0">
                    <span style="font-size:12px;color:var(--tg-text-muted)">Last Heartbeat</span>
                    <span style="font-size:12px;color:var(--tg-text-secondary)">{{ $server->last_heartbeat_at?->diffForHumans() ?? 'Never' }}</span>
                </div>
            </div>
        </div>

        @if($server->agent)
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-robot text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Agent Info</div>
            </div>
            <div class="tg-card-body">
                @foreach([
                    ['label'=>'Agent ID','value'=>$server->agent->agent_id],
                    ['label'=>'Version','value'=>$server->agent->version],
                    ['label'=>'Status','value'=>ucfirst($server->agent->status)],
                    ['label'=>'Connected','value'=>$server->agent->connected_at?->format('Y-m-d H:i') ?? '—'],
                ] as $info)
                <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
                    <span style="font-size:12px;color:var(--tg-text-muted)">{{ $info['label'] }}</span>
                    <span class="mono" style="font-size:12px;color:var(--tg-text-secondary)">{{ $info['value'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        {{-- Recent Threats --}}
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-bug text-critical" style="font-size:13px"></i>
                <div class="tg-card-title">Recent Threats</div>
                <a href="{{ route('threats.index', ['server_id' => $server->id]) }}" class="btn-tg-ghost" style="padding:5px 12px;font-size:12px">View All</a>
            </div>
            <table class="tg-table">
                <thead><tr><th>THREAT</th><th>SEVERITY</th><th>STATUS</th><th>DETECTED</th></tr></thead>
                <tbody>
                    @forelse($server->threats->take(8) as $threat)
                    <tr>
                        <td>
                            <a href="{{ route('threats.show', $threat) }}" style="color:var(--tg-text-primary);font-weight:600;font-size:13px;text-decoration:none">
                                {{ Str::limit($threat->title, 45) }}
                            </a>
                        </td>
                        <td><span class="tg-badge badge-{{ $threat->severity }}" style="font-size:10px">{{ strtoupper($threat->severity) }}</span></td>
                        <td><span style="font-size:12px;color:var(--tg-text-muted)">{{ ucwords(str_replace('_',' ',$threat->status)) }}</span></td>
                        <td style="font-size:12px;color:var(--tg-text-muted)">{{ $threat->detected_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4">
                        <div class="text-center py-4" style="color:var(--tg-text-muted);font-size:13px">
                            <i class="fas fa-shield-check" style="color:var(--tg-accent);margin-right:6px"></i>No threats detected
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Recent Scans --}}
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-magnifying-glass-chart text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Recent Scans</div>
            </div>
            <table class="tg-table">
                <thead><tr><th>SCAN ID</th><th>TYPE</th><th>STATUS</th><th>FILES</th><th>THREATS</th><th>DURATION</th><th>STARTED</th></tr></thead>
                <tbody>
                    @forelse($recentScans as $scan)
                    <tr>
                        <td class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ Str::limit($scan->scan_id,14) }}</td>
                        <td style="font-size:12px">{{ ucfirst($scan->type) }}</td>
                        <td>
                            @php $sc=['completed'=>'badge-success','running'=>'badge-low','failed'=>'badge-critical'] @endphp
                            <span class="tg-badge {{ $sc[$scan->status] ?? '' }}" style="font-size:10px">{{ strtoupper($scan->status) }}</span>
                        </td>
                        <td class="mono" style="font-size:12px">{{ number_format($scan->files_scanned) }}</td>
                        <td style="font-weight:600;color:{{ $scan->threats_found > 0 ? 'var(--tg-critical)' : 'var(--tg-accent)' }}">{{ $scan->threats_found }}</td>
                        <td class="mono" style="font-size:12px;color:var(--tg-text-muted)">{{ $scan->duration_seconds ? $scan->duration_seconds.'s' : '—' }}</td>
                        <td style="font-size:12px;color:var(--tg-text-muted)">{{ $scan->started_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="text-center py-3" style="color:var(--tg-text-muted);font-size:13px">No scans yet</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Live metrics polling
async function refreshMetrics() {
    try {
        const r = await fetch(`/servers/{{ $server->id }}/metrics`, { headers: { 'Accept': 'application/json' } });
        const d = await r.json();
        document.getElementById('cpuVal').textContent = d.cpu_usage + '%';
        document.getElementById('memVal').textContent = d.memory_usage + '%';
        document.getElementById('diskVal').textContent = d.disk_usage + '%';
        document.getElementById('cpuBar').style.width = d.cpu_usage + '%';
        document.getElementById('memBar').style.width = d.memory_usage + '%';
        document.getElementById('diskBar').style.width = d.disk_usage + '%';
    } catch(e) {}
}
setInterval(refreshMetrics, 15000);
</script>
@endpush
