@extends('layouts.app')

@section('title', 'Command Center')
@section('page-title', 'Security Command Center')

@push('styles')
<style>
.risk-ring {
    width: 110px; height: 110px;
    position: relative;
    flex-shrink: 0;
}
.risk-ring svg { transform: rotate(-90deg); }
.risk-ring-label {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.risk-ring-value {
    font-family: var(--tg-font-display);
    font-size: 28px;
    font-weight: 700;
    line-height: 1;
}
.risk-ring-text {
    font-size: 10px;
    color: var(--tg-text-muted);
    letter-spacing: 1px;
    text-transform: uppercase;
}
.server-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.03);
}
.server-item:last-child { border-bottom: none; }
.server-metrics {
    display: flex;
    gap: 14px;
    margin-left: auto;
    flex-shrink: 0;
}
.metric-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 46px;
}
.metric-pill-value {
    font-family: var(--tg-font-mono);
    font-size: 13px;
    font-weight: 600;
    color: var(--tg-text-primary);
}
.metric-pill-label {
    font-size: 9px;
    color: var(--tg-text-muted);
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
</style>
@endpush

@section('content')

{{-- Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="tg-stat-card critical">
            <div class="tg-stat-label">Critical Threats</div>
            <div class="tg-stat-value critical">{{ $stats['critical_threats'] }}</div>
            <div class="tg-stat-sub">
                <i class="fas fa-arrow-up" style="color:var(--tg-critical);font-size:10px"></i>
                <span>{{ $stats['new_today'] }} new today</span>
            </div>
            <div class="tg-stat-icon"><i class="fas fa-skull-crossbones"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="tg-stat-card high">
            <div class="tg-stat-label">Active Incidents</div>
            <div class="tg-stat-value high">{{ $stats['active_incidents'] }}</div>
            <div class="tg-stat-sub">
                <span>{{ $stats['investigating'] }} under investigation</span>
            </div>
            <div class="tg-stat-icon"><i class="fas fa-fire"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="tg-stat-card accent">
            <div class="tg-stat-label">Servers Online</div>
            <div class="tg-stat-value accent">{{ $stats['servers_online'] }}/{{ $stats['servers_total'] }}</div>
            <div class="tg-stat-sub">
                <i class="fas fa-circle" style="color:var(--tg-accent);font-size:7px"></i>
                <span>All agents active</span>
            </div>
            <div class="tg-stat-icon"><i class="fas fa-server"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="tg-stat-card medium">
            <div class="tg-stat-label">Files Quarantined</div>
            <div class="tg-stat-value medium">{{ $stats['quarantined'] }}</div>
            <div class="tg-stat-sub">
                <span>{{ $stats['scans_today'] }} scans today</span>
            </div>
            <div class="tg-stat-icon"><i class="fas fa-vault"></i></div>
        </div>
    </div>
</div>

{{-- Main Grid --}}
<div class="row g-3 mb-3">

    {{-- Live Threat Feed --}}
    <div class="col-lg-8">
        <div class="tg-card h-100">
            <div class="tg-card-header">
                <i class="fas fa-rss text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Live Threat Feed</div>
                <div class="tg-live-indicator">
                    <div class="tg-live-dot"></div>
                    REALTIME
                </div>
                <a href="{{ route('threats.index') }}" class="btn-tg-ghost ms-2" style="padding:5px 12px;font-size:12px">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="tg-card-body" id="threatFeed" style="max-height:420px;overflow-y:auto">
                @forelse($recentThreats as $threat)
                <div class="tg-feed-item">
                    <div class="tg-feed-icon {{ $threat->severity }}">
                        <i class="fas {{ match($threat->type) {
                            'web_shell' => 'fa-code',
                            'malware' => 'fa-virus',
                            'crypto_miner' => 'fa-microchip',
                            'brute_force' => 'fa-key',
                            'reverse_shell' => 'fa-terminal',
                            default => 'fa-bug'
                        } }}"></i>
                    </div>
                    <div class="tg-feed-body">
                        <div class="tg-feed-title">{{ $threat->title }}</div>
                        <div class="tg-feed-meta">
                            <span class="mono">{{ $threat->server->hostname ?? 'unknown' }}</span>
                            @if($threat->file_path)
                                · <span class="mono" style="color:var(--tg-text-muted)">{{ Str::limit($threat->file_path, 40) }}</span>
                            @endif
                            · {{ $threat->detected_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <span class="tg-badge {{ $threat->severityBadgeClass() }}">{{ $threat->severity }}</span>
                        <a href="{{ route('threats.show', $threat) }}" class="btn-tg-ghost" style="padding:3px 10px;font-size:11px">
                            Inspect
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="fas fa-shield-check" style="font-size:40px;color:var(--tg-accent);opacity:0.4;margin-bottom:12px;display:block"></i>
                    <div style="color:var(--tg-text-muted);font-size:14px">No active threats detected</div>
                    <div style="color:var(--tg-text-muted);font-size:12px;margin-top:4px">Your systems are clean</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-4 d-flex flex-column gap-3">

        {{-- Risk Score --}}
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-chart-pie text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Organization Risk</div>
            </div>
            <div class="tg-card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="risk-ring">
                        <svg width="110" height="110" viewBox="0 0 110 110">
                            <circle cx="55" cy="55" r="46" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="10"/>
                            <circle cx="55" cy="55" r="46" fill="none"
                                stroke="{{ $riskScore > 75 ? 'var(--tg-critical)' : ($riskScore > 50 ? 'var(--tg-high)' : ($riskScore > 25 ? 'var(--tg-medium)' : 'var(--tg-accent)')) }}"
                                stroke-width="10"
                                stroke-dasharray="{{ round(289 * $riskScore / 100) }} 289"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="risk-ring-label">
                            <div class="risk-ring-value" style="color:{{ $riskScore > 75 ? 'var(--tg-critical)' : ($riskScore > 50 ? 'var(--tg-high)' : ($riskScore > 25 ? 'var(--tg-medium)' : 'var(--tg-accent)')) }}">
                                {{ $riskScore }}
                            </div>
                            <div class="risk-ring-text">RISK</div>
                        </div>
                    </div>
                    <div style="flex:1">
                        @foreach(['critical' => $stats['sev_critical'], 'high' => $stats['sev_high'], 'medium' => $stats['sev_medium'], 'low' => $stats['sev_low']] as $sev => $count)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="tg-badge badge-{{ $sev }}" style="font-size:10px">{{ strtoupper($sev) }}</span>
                            <div style="flex:1;margin:0 10px">
                                <div class="tg-progress">
                                    <div class="tg-progress-bar {{ $sev === 'medium' ? 'medium' : ($sev === 'low' ? 'accent' : $sev) }}"
                                         style="width:{{ $stats['total_threats'] > 0 ? round($count / $stats['total_threats'] * 100) : 0 }}%">
                                    </div>
                                </div>
                            </div>
                            <span class="mono" style="font-size:12px;color:var(--tg-text-secondary);min-width:20px;text-align:right">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Server Status --}}
        <div class="tg-card" style="flex:1">
            <div class="tg-card-header">
                <i class="fas fa-server text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Server Status</div>
                <a href="{{ route('servers.index') }}" class="btn-tg-ghost" style="padding:4px 10px;font-size:11px">All</a>
            </div>
            <div class="tg-card-body">
                @forelse($servers as $server)
                <div class="server-item">
                    <div>
                        <div class="tg-status {{ $server->isOnline() ? 'online' : 'offline' }}">
                            <div class="tg-status-dot"></div>
                            <span style="font-size:13px;font-weight:600;color:var(--tg-text-primary)">{{ $server->name }}</span>
                        </div>
                        <div style="font-size:11px;color:var(--tg-text-muted);font-family:var(--tg-font-mono);margin-top:2px">
                            {{ $server->hostname }}
                        </div>
                    </div>
                    <div class="server-metrics">
                        <div class="metric-pill">
                            <div class="metric-pill-value {{ $server->cpu_usage > 80 ? 'text-critical' : '' }}">{{ $server->cpu_usage }}%</div>
                            <div class="metric-pill-label">CPU</div>
                        </div>
                        <div class="metric-pill">
                            <div class="metric-pill-value {{ $server->memory_usage > 85 ? 'text-high' : '' }}">{{ $server->memory_usage }}%</div>
                            <div class="metric-pill-label">MEM</div>
                        </div>
                        <div class="metric-pill">
                            <div class="metric-pill-value">{{ $server->disk_usage }}%</div>
                            <div class="metric-pill-label">DSK</div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4" style="color:var(--tg-text-muted);font-size:13px">
                    <i class="fas fa-server mb-2" style="display:block;font-size:28px;opacity:0.2"></i>
                    No servers connected
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Threat Timeline Chart --}}
<div class="row g-3">
    <div class="col-lg-8">
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-chart-line text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Threat Activity — Last 7 Days</div>
                <div class="d-flex gap-2">
                    <span class="tg-badge badge-critical" style="font-size:10px">Critical</span>
                    <span class="tg-badge badge-high" style="font-size:10px">High</span>
                    <span class="tg-badge badge-medium" style="font-size:10px">Medium</span>
                </div>
            </div>
            <div class="tg-card-body">
                <canvas id="threatChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="tg-card h-100">
            <div class="tg-card-header">
                <i class="fas fa-shield-virus text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Threat Breakdown</div>
            </div>
            <div class="tg-card-body">
                <canvas id="typeChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Threat Timeline Chart ────────────────────────────────────
const labels = @json($chartData['labels']);
const criticalData = @json($chartData['critical']);
const highData = @json($chartData['high']);
const mediumData = @json($chartData['medium']);

const ctx = document.getElementById('threatChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Critical',
                data: criticalData,
                borderColor: '#ff3366',
                backgroundColor: 'rgba(255,51,102,0.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ff3366',
                pointRadius: 3,
            },
            {
                label: 'High',
                data: highData,
                borderColor: '#ff6b35',
                backgroundColor: 'rgba(255,107,53,0.06)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ff6b35',
                pointRadius: 3,
            },
            {
                label: 'Medium',
                data: mediumData,
                borderColor: '#ffd700',
                backgroundColor: 'rgba(255,215,0,0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ffd700',
                pointRadius: 3,
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0d1526',
                borderColor: 'rgba(0,255,136,0.1)',
                borderWidth: 1,
                titleColor: '#e8f0fe',
                bodyColor: '#8099c0',
                padding: 12,
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.03)' },
                ticks: { color: '#4a6080', font: { size: 11 } },
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.03)' },
                ticks: { color: '#4a6080', font: { size: 11 }, stepSize: 1 },
                beginAtZero: true,
            }
        }
    }
});

// ── Type Donut Chart ──────────────────────────────────────────
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: @json(array_keys($threatTypes)),
        datasets: [{
            data: @json(array_values($threatTypes)),
            backgroundColor: ['#ff3366','#ff6b35','#ffd700','#00d4ff','#00ff88','#9b59b6','#e74c3c'],
            borderWidth: 2,
            borderColor: '#0d1526',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#8099c0', font: { size: 11 }, padding: 12, boxWidth: 10 }
            },
            tooltip: {
                backgroundColor: '#0d1526',
                borderColor: 'rgba(0,255,136,0.1)',
                borderWidth: 1,
                titleColor: '#e8f0fe',
                bodyColor: '#8099c0',
            }
        }
    }
});

// ── Real-time Feed via polling (replace with WebSocket) ───────
let lastId = 0;
async function pollThreats() {
    try {
        const r = await fetch(`/api/v1/dashboard/threats/recent?after=${lastId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        if (!r.ok) return;
        const data = await r.json();
        if (data.threats && data.threats.length > 0) {
            data.threats.forEach(t => {
                lastId = Math.max(lastId, t.id);
                prependThreat(t);
                showToast('New Threat Detected', t.title, t.severity);
            });
        }
    } catch(e) {}
}

function prependThreat(t) {
    const feed = document.getElementById('threatFeed');
    const icons = {
        web_shell: 'fa-code', malware: 'fa-virus',
        crypto_miner: 'fa-microchip', brute_force: 'fa-key',
        reverse_shell: 'fa-terminal', default: 'fa-bug'
    };
    const icon = icons[t.type] || icons.default;
    const item = document.createElement('div');
    item.className = 'tg-feed-item';
    item.innerHTML = `
        <div class="tg-feed-icon ${t.severity}"><i class="fas ${icon}"></i></div>
        <div class="tg-feed-body">
            <div class="tg-feed-title">${t.title}</div>
            <div class="tg-feed-meta mono">${t.server || ''} · just now</div>
        </div>
        <span class="tg-badge badge-${t.severity}">${t.severity}</span>
    `;
    feed.insertBefore(item, feed.firstChild);
}

setInterval(pollThreats, 8000);
</script>
@endpush
