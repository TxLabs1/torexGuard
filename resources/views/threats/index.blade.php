{{-- resources/views/threats/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Threats')
@section('page-title', 'Threat Intelligence')

@section('content')
{{-- Filters --}}
<div class="tg-card mb-4">
    <div class="tg-card-body">
        <form method="GET" action="{{ route('threats.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="tg-form-label">Search</label>
                <input type="text" name="search" class="tg-form-control" placeholder="File path, IP, threat ID..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">Severity</label>
                <select name="severity" class="tg-select">
                    <option value="">All Severities</option>
                    @foreach(['critical','high','medium','low'] as $s)
                        <option value="{{ $s }}" {{ request('severity') == $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">Status</label>
                <select name="status" class="tg-select">
                    <option value="">All Statuses</option>
                    @foreach(['active','investigating','quarantined','resolved','false_positive'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_',' ',$s)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">Type</label>
                <select name="type" class="tg-select">
                    <option value="">All Types</option>
                    @foreach(['web_shell','malware','reverse_shell','crypto_miner','backdoor','brute_force','sql_injection'] as $t)
                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_',' ',$t)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">Server</label>
                <select name="server_id" class="tg-select">
                    <option value="">All Servers</option>
                    @foreach($servers as $server)
                        <option value="{{ $server->id }}" {{ request('server_id') == $server->id ? 'selected' : '' }}>
                            {{ $server->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn-tg-primary" style="padding:9px 14px">
                    <i class="fas fa-filter"></i>
                </button>
                @if(request()->hasAny(['search','severity','status','type','server_id']))
                    <a href="{{ route('threats.index') }}" class="btn-tg-ghost" style="padding:9px 12px">
                        <i class="fas fa-xmark"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Summary badges --}}
<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach(['critical' => $counts['critical'], 'high' => $counts['high'], 'medium' => $counts['medium'], 'low' => $counts['low']] as $sev => $count)
    <a href="{{ route('threats.index', ['severity' => $sev]) }}"
       class="tg-badge badge-{{ $sev }}" style="padding:6px 12px;font-size:12px;text-decoration:none">
        <i class="fas fa-circle" style="font-size:7px"></i>
        {{ strtoupper($sev) }}: {{ $count }}
    </a>
    @endforeach
    <span class="tg-badge" style="background:rgba(255,255,255,0.04);color:var(--tg-text-secondary);border:1px solid var(--tg-border);padding:6px 12px;font-size:12px">
        TOTAL: {{ $threats->total() }}
    </span>
</div>

{{-- Threats Table --}}
<div class="tg-card">
    <div class="tg-card-header">
        <i class="fas fa-bug text-critical" style="font-size:13px"></i>
        <div class="tg-card-title">Active Threats</div>
        <div class="tg-live-indicator">
            <div class="tg-live-dot"></div>
            LIVE
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="tg-table">
            <thead>
                <tr>
                    <th>THREAT ID</th>
                    <th>TITLE</th>
                    <th>TYPE</th>
                    <th>SEVERITY</th>
                    <th>SERVER</th>
                    <th>STATUS</th>
                    <th>CONFIDENCE</th>
                    <th>DETECTED</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($threats as $threat)
                <tr>
                    <td>
                        <span class="mono text-accent" style="font-size:12px">{{ $threat->threat_id }}</span>
                    </td>
                    <td style="max-width:220px">
                        <div style="font-weight:600;color:var(--tg-text-primary);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $threat->title }}
                        </div>
                        @if($threat->file_path)
                        <div class="mono" style="font-size:11px;color:var(--tg-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $threat->file_path }}
                        </div>
                        @endif
                    </td>
                    <td>
                        <span style="font-size:12px;color:var(--tg-text-secondary)">
                            {{ ucwords(str_replace('_', ' ', $threat->type)) }}
                        </span>
                    </td>
                    <td>
                        <span class="tg-badge badge-{{ $threat->severity }}">
                            {{ strtoupper($threat->severity) }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:13px;color:var(--tg-text-secondary)">{{ $threat->server->name ?? '—' }}</div>
                        <div class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ $threat->server->ip_address ?? '' }}</div>
                    </td>
                    <td>
                        @php
                        $statusColors = [
                            'active' => 'var(--tg-critical)',
                            'investigating' => 'var(--tg-medium)',
                            'quarantined' => 'var(--tg-high)',
                            'resolved' => 'var(--tg-accent)',
                            'false_positive' => 'var(--tg-text-muted)',
                        ];
                        $color = $statusColors[$threat->status] ?? 'var(--tg-text-muted)';
                        @endphp
                        <span style="font-size:12px;font-weight:600;color:{{ $color }};font-family:var(--tg-font-mono)">
                            {{ strtoupper(str_replace('_', ' ', $threat->status)) }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="tg-progress" style="width:60px">
                                <div class="tg-progress-bar {{ $threat->confidence_score > 75 ? 'critical' : ($threat->confidence_score > 50 ? 'medium' : 'accent') }}"
                                     style="width:{{ $threat->confidence_score }}%"></div>
                            </div>
                            <span class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ $threat->confidence_score }}%</span>
                        </div>
                    </td>
                    <td>
                        <span style="font-size:12px;color:var(--tg-text-muted)">{{ $threat->detected_at->diffForHumans() }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('threats.show', $threat) }}" class="btn-tg-ghost" style="padding:5px 10px;font-size:11px">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($threat->status === 'active')
                            <button onclick="quickAction('{{ $threat->id }}', 'quarantine')"
                                    class="btn-tg-danger" style="padding:5px 10px;font-size:11px"
                                    title="Quarantine">
                                <i class="fas fa-vault"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="text-center py-5">
                            <i class="fas fa-shield-check" style="font-size:48px;color:var(--tg-accent);opacity:0.3;display:block;margin-bottom:14px"></i>
                            <div style="color:var(--tg-text-muted);font-size:15px;font-family:var(--tg-font-display)">No threats found</div>
                            <div style="color:var(--tg-text-muted);font-size:12px;margin-top:4px">Your systems are protected</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($threats->hasPages())
    <div class="tg-card-body border-top" style="border-color:var(--tg-border)">
        <div class="d-flex justify-content-between align-items-center">
            <div style="font-size:12px;color:var(--tg-text-muted)">
                Showing {{ $threats->firstItem() }}–{{ $threats->lastItem() }} of {{ $threats->total() }} threats
            </div>
            {{ $threats->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
async function quickAction(threatId, action) {
    if (!confirm(`Are you sure you want to ${action} this threat?`)) return;
    try {
        const r = await fetch(`/threats/${threatId}/${action}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        });
        const d = await r.json();
        if (d.success) {
            showToast('Action executed', d.message || `Threat ${action}d successfully`, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    } catch(e) {
        showToast('Error', 'Action failed. Please try again.', 'critical');
    }
}
</script>
@endpush
