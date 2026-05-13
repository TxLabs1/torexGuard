@extends('layouts.app')
@section('title', $project->name)
@section('page-title', $project->name)

@section('content')
<div class="row g-3 mb-4">
    {{-- Project Info --}}
    <div class="col-lg-8">
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-folder-open text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Project Overview</div>
                <span class="tg-badge {{ $project->environment === 'production' ? 'badge-critical' : 'badge-low' }}">
                    {{ strtoupper($project->environment) }}
                </span>
                <a href="{{ route('projects.edit', $project) }}" class="btn-tg-ghost ms-2" style="padding:5px 12px;font-size:12px">
                    <i class="fas fa-pen"></i> Edit
                </a>
            </div>
            <div class="tg-card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="tg-form-label">Project Name</div>
                        <div style="color:var(--tg-text-primary);font-weight:600">{{ $project->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="tg-form-label">Type</div>
                        <div style="color:var(--tg-text-primary)">{{ strtoupper($project->type) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="tg-form-label">Risk Score</div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="tg-progress" style="width:120px">
                                <div class="tg-progress-bar {{ $project->risk_score > 75 ? 'critical' : 'accent' }}"
                                     style="width:{{ $project->risk_score }}%"></div>
                            </div>
                            <span class="mono" style="font-size:14px;font-weight:700;color:{{ $project->risk_score > 75 ? 'var(--tg-critical)' : 'var(--tg-accent)' }}">
                                {{ $project->risk_score }}/100
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="tg-form-label">Status</div>
                        <span class="tg-status {{ $project->status === 'active' ? 'online' : 'offline' }}">
                            <div class="tg-status-dot"></div>
                            {{ ucfirst($project->status) }}
                        </span>
                    </div>
                    @if($project->description)
                    <div class="col-12">
                        <div class="tg-form-label">Description</div>
                        <div style="color:var(--tg-text-secondary)">{{ $project->description }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="col-lg-4 d-flex flex-column gap-3">
        <div class="tg-stat-card critical">
            <div class="tg-stat-label">Active Threats</div>
            <div class="tg-stat-value critical">{{ $project->threats->where('status','active')->count() }}</div>
            <div class="tg-stat-icon"><i class="fas fa-bug"></i></div>
        </div>
        <div class="tg-stat-card accent">
            <div class="tg-stat-label">Servers Connected</div>
            <div class="tg-stat-value accent">{{ $project->servers->count() }}</div>
            <div class="tg-stat-icon"><i class="fas fa-server"></i></div>
        </div>
    </div>
</div>

{{-- API Credentials --}}
@php
    $plaintextKey    = session('plaintext_api_key');
    $plaintextSecret = session('plaintext_api_secret');
    $displayKey      = $plaintextKey ?? $project->maskedApiKey();
@endphp
<div class="tg-card mb-4">
    <div class="tg-card-header">
        <i class="fas fa-key text-accent" style="font-size:13px"></i>
        <div class="tg-card-title">API Credentials</div>
        <button class="btn-tg-danger" onclick="rotateKey({{ $project->id }})" style="padding:6px 14px;font-size:12px">
            <i class="fas fa-rotate"></i> Rotate Key
        </button>
    </div>
    <div class="tg-card-body">
        @if($plaintextKey)
            <div class="alert alert-warning" style="background:#3a2a00;border:1px solid #b58900;color:#ffd966;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px">
                <strong>Copy this API key now.</strong> For security, we hash it on the server and cannot show it again.
            </div>
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <div class="tg-form-label">API Key</div>
                <div class="d-flex gap-2">
                    <div class="tg-form-control mono" style="flex:1;font-size:13px;cursor:default">
                        {{ $displayKey }}
                    </div>
                    @if($plaintextKey)
                        <button class="btn-tg-ghost" onclick="copyToClipboard('{{ $plaintextKey }}')" style="padding:8px 12px">
                            <i class="fas fa-copy"></i>
                        </button>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="tg-form-label">API Secret (never share)</div>
                <div class="d-flex gap-2">
                    <div class="tg-form-control mono" id="secretField" style="flex:1;font-size:13px;cursor:default;letter-spacing:3px">
                        {{ $plaintextSecret ?? '••••••••••••••••••••' }}
                    </div>
                    @if($plaintextSecret)
                        <button class="btn-tg-ghost" onclick="copyToClipboard('{{ $plaintextSecret }}')" style="padding:8px 12px">
                            <i class="fas fa-copy"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Agent Install Instructions --}}
<div class="tg-card mb-4">
    <div class="tg-card-header">
        <i class="fas fa-terminal text-accent" style="font-size:13px"></i>
        <div class="tg-card-title">Agent Installation</div>
        <span class="tg-badge badge-success">Quick Setup</span>
    </div>
    <div class="tg-card-body">
        <div class="mb-4">
            <div class="tg-form-label mb-2">1. Install Agent (Linux/macOS)</div>
            <div style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:16px;font-family:var(--tg-font-mono);font-size:13px;line-height:1.8">
                <span style="color:var(--tg-text-muted)"># One-line install (verifies SHA256SUMS)</span><br>
                <span style="color:var(--tg-accent)">curl</span> -fsSL {{ url('/install.sh') }} | bash -s -- --api-key={{ $plaintextKey ?? '<your-api-key>' }} --host={{ config('app.url') }}
            </div>
        </div>

        <div class="mb-4">
            <div class="tg-form-label mb-2">1b. Install Agent (Windows)</div>
            <div style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:16px;font-family:var(--tg-font-mono);font-size:13px;line-height:1.8">
                <span style="color:var(--tg-text-muted)"># Run in elevated PowerShell</span><br>
                <span style="color:var(--tg-accent)">iex (iwr "{{ url('/install.ps1') }}").Content</span>; Install-TorexAgent -ApiKey "{{ $plaintextKey ?? '<your-api-key>' }}" -Host "{{ config('app.url') }}"
            </div>
        </div>

        <div class="mb-4">
            <div class="tg-form-label mb-2">2. Configure Agent</div>
            <div style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:16px;font-family:var(--tg-font-mono);font-size:13px;line-height:1.8">
                <span style="color:var(--tg-text-muted)"># Create config file</span><br>
                <span style="color:var(--tg-accent)">torex-agent</span> init --api-key={{ $plaintextKey ?? '<your-api-key>' }} --host={{ config('app.url') }}
            </div>
        </div>

        <div class="mb-4">
            <div class="tg-form-label mb-2">3. Start Agent</div>
            <div style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:16px;font-family:var(--tg-font-mono);font-size:13px;line-height:1.8">
                <span style="color:var(--tg-accent)">torex-agent</span> start --daemon
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="tg-form-label mb-2">Laravel SDK</div>
                <div style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:14px;font-family:var(--tg-font-mono);font-size:13px;line-height:1.8">
                    <span style="color:var(--tg-accent)">composer</span> require torexlabs/guard<br>
                    <span style="color:var(--tg-accent)">php artisan</span> guard:install
                </div>
            </div>
            <div class="col-md-6">
                <div class="tg-form-label mb-2">Python SDK</div>
                <div style="background:#040810;border:1px solid var(--tg-border);border-radius:10px;padding:14px;font-family:var(--tg-font-mono);font-size:13px;line-height:1.8">
                    <span style="color:var(--tg-accent)">pip install</span> torexguard<br>
                    from torexguard import Guard<br>
                    Guard.init(api_key='{{ $plaintextKey ?? '<your-api-key>' }}')
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Servers --}}
<div class="tg-card">
    <div class="tg-card-header">
        <i class="fas fa-server text-accent" style="font-size:13px"></i>
        <div class="tg-card-title">Connected Servers</div>
    </div>
    <table class="tg-table">
        <thead>
            <tr><th>SERVER</th><th>STATUS</th><th>AGENT</th><th>CPU</th><th>MEM</th><th>THREATS</th><th>LAST SEEN</th></tr>
        </thead>
        <tbody>
            @forelse($project->servers as $server)
            <tr>
                <td>
                    <a href="{{ route('servers.show', $server) }}" style="color:var(--tg-text-primary);font-weight:600;text-decoration:none">{{ $server->name }}</a>
                    <div class="mono" style="font-size:11px;color:var(--tg-text-muted)">{{ $server->hostname }}</div>
                </td>
                <td>
                    <span class="tg-status {{ $server->isOnline() ? 'online' : 'offline' }}">
                        <div class="tg-status-dot"></div>
                        {{ $server->isOnline() ? 'Online' : 'Offline' }}
                    </span>
                </td>
                <td>
                    @if($server->agent)
                        <span class="tg-badge badge-success" style="font-size:10px">{{ $server->agent->version }}</span>
                    @else
                        <span class="tg-badge" style="background:rgba(255,255,255,0.04);color:var(--tg-text-muted);border:1px solid var(--tg-border);font-size:10px">NOT INSTALLED</span>
                    @endif
                </td>
                <td><span class="mono" style="font-size:13px">{{ $server->cpu_usage }}%</span></td>
                <td><span class="mono" style="font-size:13px">{{ $server->memory_usage }}%</span></td>
                <td>
                    <span style="font-size:13px;{{ $server->threats->count() > 0 ? 'color:var(--tg-critical)' : 'color:var(--tg-accent)' }}">
                        {{ $server->threats->count() }}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--tg-text-muted)">
                    {{ $server->last_heartbeat_at?->diffForHumans() ?? 'Never' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="7">
                <div class="text-center py-4" style="color:var(--tg-text-muted);font-size:13px">
                    No servers connected yet — install the agent to get started
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
const secretValue = '{{ $project->api_secret ?? "" }}';
let secretVisible = false;

function toggleSecret() {
    secretVisible = !secretVisible;
    document.getElementById('secretField').textContent = secretVisible ? secretValue : '••••••••••••••••••••';
    document.getElementById('secretField').style.letterSpacing = secretVisible ? '0' : '3px';
    document.getElementById('secretEyeIcon').className = secretVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied', 'API key copied to clipboard', 'success');
    });
}

async function rotateKey(projectId) {
    if (!confirm('Rotating the API key will disconnect all agents. Continue?')) return;
    const r = await fetch(`/projects/${projectId}/rotate-key`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const d = await r.json();
    if (d.success) {
        showToast('Key Rotated', 'New API key generated', 'success');
        setTimeout(() => location.reload(), 1500);
    }
}
</script>
@endpush
