{{-- resources/views/incidents/index.blade.php --}}
@extends('layouts.app')
@section('title','Incidents')
@section('page-title','Incident Management')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="tg-stat-card critical">
            <div class="tg-stat-label">Critical Incidents</div>
            <div class="tg-stat-value critical">{{ $incidents->where('severity','critical')->count() }}</div>
            <div class="tg-stat-icon"><i class="fas fa-skull-crossbones"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tg-stat-card high">
            <div class="tg-stat-label">High Severity</div>
            <div class="tg-stat-value high">{{ $incidents->where('severity','high')->count() }}</div>
            <div class="tg-stat-icon"><i class="fas fa-fire"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tg-stat-card medium">
            <div class="tg-stat-label">Under Investigation</div>
            <div class="tg-stat-value medium">{{ $incidents->where('status','investigating')->count() }}</div>
            <div class="tg-stat-icon"><i class="fas fa-magnifying-glass"></i></div>
        </div>
    </div>
</div>

<div class="tg-card">
    <div class="tg-card-header">
        <i class="fas fa-triangle-exclamation text-high" style="font-size:13px"></i>
        <div class="tg-card-title">Active Incidents</div>
        <div class="tg-live-indicator"><div class="tg-live-dot"></div>LIVE</div>
    </div>
    <table class="tg-table">
        <thead>
            <tr><th>INCIDENT ID</th><th>TITLE</th><th>SEVERITY</th><th>SERVER</th><th>STATUS</th><th>DETECTED</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($incidents as $incident)
            <tr>
                <td class="mono" style="font-size:12px;color:var(--tg-accent)">{{ $incident->threat_id }}</td>
                <td style="max-width:240px">
                    <div style="font-weight:600;color:var(--tg-text-primary);font-size:13px">{{ Str::limit($incident->title,50) }}</div>
                    <div style="font-size:11px;color:var(--tg-text-muted)">{{ ucwords(str_replace('_',' ',$incident->type)) }}</div>
                </td>
                <td><span class="tg-badge badge-{{ $incident->severity }}">{{ strtoupper($incident->severity) }}</span></td>
                <td style="font-size:13px;color:var(--tg-text-secondary)">{{ $incident->server->hostname ?? '—' }}</td>
                <td>
                    @php $c=['active'=>'var(--tg-critical)','investigating'=>'var(--tg-medium)','quarantined'=>'var(--tg-high)'] @endphp
                    <span class="mono" style="font-size:11px;font-weight:600;color:{{ $c[$incident->status] ?? 'var(--tg-text-muted)' }}">
                        {{ strtoupper(str_replace('_',' ',$incident->status)) }}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--tg-text-muted)">{{ $incident->detected_at->diffForHumans() }}</td>
                <td>
                    <a href="{{ route('threats.show', $incident) }}" class="btn-tg-primary" style="padding:5px 12px;font-size:12px">
                        <i class="fas fa-eye"></i> Investigate
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7">
                <div class="text-center py-5">
                    <i class="fas fa-shield-check" style="font-size:48px;color:var(--tg-accent);opacity:0.3;display:block;margin-bottom:14px"></i>
                    <div style="font-family:var(--tg-font-display);font-size:18px;margin-bottom:6px">No active incidents</div>
                    <div style="color:var(--tg-text-muted);font-size:13px">All critical and high threats are resolved</div>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($incidents->hasPages())
    <div class="tg-card-body" style="border-top:1px solid var(--tg-border)">{{ $incidents->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
