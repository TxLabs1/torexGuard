{{-- resources/views/scans/index.blade.php --}}
@extends('layouts.app')
@section('title','Scans')
@section('page-title','Security Scans')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div style="font-size:13px;color:var(--tg-text-muted)">{{ $scans->total() }} scan{{ $scans->total() !== 1 ? 's' : '' }} total</div>
    <button class="btn-tg-primary" data-bs-toggle="modal" data-bs-target="#startScanModal">
        <i class="fas fa-magnifying-glass-chart"></i> Run Scan
    </button>
</div>

<div class="tg-card">
    <table class="tg-table">
        <thead>
            <tr><th>SCAN ID</th><th>PROJECT</th><th>SERVER</th><th>TYPE</th><th>STATUS</th><th>FILES SCANNED</th><th>THREATS</th><th>DURATION</th><th>STARTED</th></tr>
        </thead>
        <tbody>
            @forelse($scans as $scan)
            <tr>
                <td class="mono" style="font-size:11px;color:var(--tg-accent)">{{ Str::limit($scan->scan_id, 16) }}</td>
                <td style="font-size:13px">{{ $scan->project->name ?? '—' }}</td>
                <td style="font-size:13px;color:var(--tg-text-secondary)">{{ $scan->server->hostname ?? '—' }}</td>
                <td><span class="tg-badge badge-low" style="font-size:10px">{{ strtoupper($scan->type) }}</span></td>
                <td>
                    @php $sc=['completed'=>'badge-success','running'=>'badge-medium','failed'=>'badge-critical','cancelled'=>''] @endphp
                    <span class="tg-badge {{ $sc[$scan->status] ?? '' }}" style="font-size:10px">
                        {{ strtoupper($scan->status) }}
                    </span>
                </td>
                <td class="mono" style="font-size:13px">{{ number_format($scan->files_scanned) }}</td>
                <td>
                    <span style="font-weight:700;font-size:14px;color:{{ $scan->threats_found > 0 ? 'var(--tg-critical)' : 'var(--tg-accent)' }}">
                        {{ $scan->threats_found }}
                    </span>
                </td>
                <td class="mono" style="font-size:12px;color:var(--tg-text-muted)">{{ $scan->duration_seconds ? $scan->duration_seconds.'s' : '—' }}</td>
                <td style="font-size:12px;color:var(--tg-text-muted)">{{ $scan->started_at->format('Y-m-d H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="9">
                <div class="text-center py-5">
                    <i class="fas fa-magnifying-glass" style="font-size:48px;color:var(--tg-accent);opacity:0.25;display:block;margin-bottom:14px"></i>
                    <div style="color:var(--tg-text-muted)">No scans run yet</div>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($scans->hasPages())
    <div class="tg-card-body" style="border-top:1px solid var(--tg-border)">{{ $scans->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

{{-- Start Scan Modal --}}
<div class="modal fade" id="startScanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--tg-bg-card);border:1px solid var(--tg-border);border-radius:16px">
            <div class="modal-header" style="border-bottom:1px solid var(--tg-border);padding:20px 24px">
                <h5 class="modal-title" style="font-family:var(--tg-font-display);font-size:18px;font-weight:700">
                    <i class="fas fa-magnifying-glass-chart text-accent me-2"></i>Run Security Scan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('scans.start') }}">
                @csrf
                <div class="modal-body" style="padding:24px">
                    <div class="mb-3">
                        <label class="tg-form-label">Select Server</label>
                        <select name="server_id" class="tg-select" required>
                            <option value="">Choose a server...</option>
                            @foreach(\App\Models\Server::where('status','online')->get() as $srv)
                            <option value="{{ $srv->id }}">{{ $srv->name }} — {{ $srv->hostname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="tg-form-label">Scan Type</label>
                        <select name="type" class="tg-select">
                            <option value="quick">Quick Scan (uploads + public only)</option>
                            <option value="full">Full Scan (entire project)</option>
                            <option value="targeted">Targeted (specific paths)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--tg-border);padding:16px 24px;gap:10px">
                    <button type="button" class="btn-tg-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-tg-primary"><i class="fas fa-play"></i> Start Scan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
