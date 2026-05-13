@extends('layouts.app')
@section('title', 'Threat — ' . $threat->threat_id)
@section('page-title', 'Threat Detail')

@section('content')
{{-- Header --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('threats.index') }}" class="btn-tg-ghost" style="padding:7px 14px;font-size:13px">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <div class="mono" style="font-size:13px;color:var(--tg-text-muted)">{{ $threat->threat_id }}</div>
    <span class="tg-badge badge-{{ $threat->severity }}" style="font-size:12px">{{ strtoupper($threat->severity) }}</span>
    @if($threat->auto_remediated)
        <span class="tg-badge badge-success" style="font-size:11px"><i class="fas fa-robot" style="font-size:9px"></i> AUTO-REMEDIATED</span>
    @endif
    <div class="ms-auto d-flex gap-2">
        @if($threat->status === 'active')
        <button onclick="takeAction('quarantine')" class="btn-tg-danger">
            <i class="fas fa-vault"></i> Quarantine
        </button>
        <button onclick="takeAction('investigate')" class="btn-tg-ghost">
            <i class="fas fa-magnifying-glass"></i> Investigate
        </button>
        <button onclick="takeAction('resolve')" class="btn-tg-primary">
            <i class="fas fa-check"></i> Resolve
        </button>
        @endif
        @if($threat->status === 'active')
        <button onclick="takeAction('false-positive')" class="btn-tg-ghost" style="color:var(--tg-text-muted)">
            <i class="fas fa-ban"></i> False Positive
        </button>
        @endif
    </div>
</div>

<div class="row g-3">
    {{-- Main info --}}
    <div class="col-lg-8">
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-shield-virus text-critical" style="font-size:13px"></i>
                <div class="tg-card-title">{{ $threat->title }}</div>
            </div>
            <div class="tg-card-body">
                @if($threat->description)
                <p style="color:var(--tg-text-secondary);font-size:14px;margin-bottom:24px">{{ $threat->description }}</p>
                @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="tg-form-label">Threat Type</div>
                        <div style="color:var(--tg-text-primary);font-weight:600">{{ ucwords(str_replace('_',' ',$threat->type)) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="tg-form-label">Status</div>
                        <div style="font-weight:600;color:var(--tg-{{ in_array($threat->status,['active']) ? 'critical' : ($threat->status === 'resolved' ? 'accent' : 'medium') }})">
                            {{ ucwords(str_replace('_', ' ', $threat->status)) }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="tg-form-label">Detected At</div>
                        <div class="mono" style="font-size:13px">{{ $threat->detected_at->format('Y-m-d H:i:s') }} UTC</div>
                    </div>
                    <div class="col-md-6">
                        <div class="tg-form-label">Confidence Score</div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="tg-progress" style="width:100px">
                                <div class="tg-progress-bar {{ $threat->confidence_score > 75 ? 'critical' : 'medium' }}"
                                     style="width:{{ $threat->confidence_score }}%"></div>
                            </div>
                            <span class="mono" style="font-size:14px;font-weight:700">{{ $threat->confidence_score }}%</span>
                        </div>
                    </div>
                    @if($threat->file_path)
                    <div class="col-12">
                        <div class="tg-form-label">Infected File</div>
                        <div style="background:#040810;border:1px solid var(--tg-border);border-radius:8px;padding:12px;font-family:var(--tg-font-mono);font-size:13px;color:var(--tg-critical)">
                            {{ $threat->file_path }}
                        </div>
                    </div>
                    @endif
                    @if($threat->file_hash)
                    <div class="col-md-6">
                        <div class="tg-form-label">File Hash (SHA256)</div>
                        <div class="mono" style="font-size:12px;color:var(--tg-text-muted);word-break:break-all">{{ $threat->file_hash }}</div>
                    </div>
                    @endif
                    @if($threat->file_size)
                    <div class="col-md-3">
                        <div class="tg-form-label">File Size</div>
                        <div class="mono" style="font-size:13px">{{ round($threat->file_size / 1024, 1) }} KB</div>
                    </div>
                    @endif
                    @if($threat->source_ip)
                    <div class="col-md-3">
                        <div class="tg-form-label">Source IP</div>
                        <div class="mono" style="color:var(--tg-high)">{{ $threat->source_ip }}</div>
                        @if($threat->source_country)
                        <div style="font-size:11px;color:var(--tg-text-muted)">{{ $threat->source_country }}</div>
                        @endif
                    </div>
                    @endif
                    @if($threat->process_name)
                    <div class="col-md-6">
                        <div class="tg-form-label">Process</div>
                        <div class="mono" style="font-size:13px">{{ $threat->process_name }} (PID: {{ $threat->process_id }})</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detection Details --}}
        @if($threat->detection_details || $threat->signatures_matched)
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-microscope text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Detection Analysis</div>
            </div>
            <div class="tg-card-body">
                @if($threat->signatures_matched)
                <div class="tg-form-label mb-2">Signatures Matched</div>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @foreach($threat->signatures_matched as $sig)
                    <span class="tg-badge badge-critical">{{ $sig }}</span>
                    @endforeach
                </div>
                @endif
                @if($threat->detection_details)
                <div class="tg-form-label mb-2">Raw Detection Data</div>
                <pre style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:16px;font-family:var(--tg-font-mono);font-size:12px;color:var(--tg-text-secondary);overflow-x:auto;margin:0">{{ json_encode($threat->detection_details, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>
        @endif

        {{-- Audit Log --}}
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-clock-rotate-left text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Audit Trail</div>
            </div>
            <div class="tg-card-body">
                @forelse($threat->logs as $log)
                <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--tg-accent-dim);display:flex;align-items:center;justify-content:center;font-size:12px;color:var(--tg-accent);flex-shrink:0">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--tg-text-primary)">
                            {{ ucwords(str_replace('_',' ',$log->action)) }}
                            <span style="font-weight:400;color:var(--tg-text-muted)">by {{ $log->user?->name ?? 'System' }}</span>
                        </div>
                        @if($log->note)
                        <div style="font-size:12px;color:var(--tg-text-muted)">{{ $log->note }}</div>
                        @endif
                        <div class="mono" style="font-size:11px;color:var(--tg-text-muted);margin-top:3px">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                </div>
                @empty
                <div style="color:var(--tg-text-muted);font-size:13px;text-align:center;padding:20px 0">
                    No audit entries yet
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Server --}}
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-server text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Affected Server</div>
            </div>
            <div class="tg-card-body">
                <div class="tg-form-label">Hostname</div>
                <div style="font-weight:600;margin-bottom:12px">{{ $threat->server->hostname ?? '—' }}</div>
                <div class="tg-form-label">IP Address</div>
                <div class="mono" style="margin-bottom:12px">{{ $threat->server->ip_address ?? '—' }}</div>
                <div class="tg-form-label">OS</div>
                <div style="margin-bottom:12px">{{ $threat->server->os ?? '—' }}</div>
                <a href="{{ route('servers.show', $threat->server_id) }}" class="btn-tg-ghost" style="width:100%;justify-content:center">
                    View Server <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        {{-- Quarantine info --}}
        @if($threat->quarantineFile)
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-vault text-medium" style="font-size:13px"></i>
                <div class="tg-card-title">Quarantine Info</div>
            </div>
            <div class="tg-card-body">
                <div class="tg-form-label">Quarantine Path</div>
                <div class="mono" style="font-size:12px;color:var(--tg-text-muted);word-break:break-all;margin-bottom:12px">
                    {{ $threat->quarantineFile->quarantine_path }}
                </div>
                <div class="tg-form-label">Quarantined At</div>
                <div style="font-size:13px;margin-bottom:12px">{{ $threat->quarantineFile->quarantined_at->format('Y-m-d H:i') }}</div>
                <button onclick="restoreFile({{ $threat->quarantineFile->id }})" class="btn-tg-ghost" style="width:100%;justify-content:center">
                    <i class="fas fa-rotate-left"></i> Restore File
                </button>
            </div>
        </div>
        @endif

        {{-- Actions taken --}}
        @if($threat->actions->count())
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-bolt text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Actions Taken</div>
            </div>
            <div class="tg-card-body">
                @foreach($threat->actions as $action)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.03)">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--tg-text-primary)">
                            {{ ucwords(str_replace('_',' ',$action->type)) }}
                        </div>
                        <div style="font-size:11px;color:var(--tg-text-muted);font-family:var(--tg-font-mono)">{{ $action->created_at->format('H:i:s') }}</div>
                    </div>
                    <span class="tg-badge {{ $action->status === 'success' ? 'badge-success' : 'badge-critical' }}" style="font-size:10px">
                        {{ strtoupper($action->status) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
async function takeAction(action) {
    const messages = {
        quarantine: 'Quarantine this file? The agent will move it to the secure quarantine vault.',
        resolve: 'Mark this threat as resolved?',
        investigate: 'Set threat status to investigating?',
        'false-positive': 'Mark as false positive? This threat will be dismissed.',
    };
    if (!confirm(messages[action] || 'Proceed?')) return;

    const r = await fetch(`/threats/{{ $threat->id }}/${action}`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const d = await r.json();
    if (d.success) {
        showToast('Done', d.message, 'success');
        setTimeout(() => location.reload(), 1500);
    }
}

async function restoreFile(fileId) {
    if (!confirm('Restore this file to its original location?')) return;
    const r = await fetch(`/quarantine/${fileId}/restore`, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF } });
    const d = await r.json();
    if (d.success) showToast('Queued', d.message, 'success');
}
</script>
@endpush
