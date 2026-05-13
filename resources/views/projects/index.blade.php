{{-- resources/views/projects/index.blade.php --}}
@extends('layouts.app')
@section('title','Projects')
@section('page-title','Projects')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div style="font-size:13px;color:var(--tg-text-muted)">{{ $projects->total() }} project{{ $projects->total() !== 1 ? 's' : '' }} connected</div>
    <a href="{{ route('projects.create') }}" class="btn-tg-primary">
        <i class="fas fa-plus"></i> New Project
    </a>
</div>

<div class="row g-3">
    @forelse($projects as $project)
    <div class="col-md-6 col-lg-4">
        <div class="tg-card h-100" style="cursor:pointer" onclick="location.href='{{ route('projects.show', $project) }}'">
            <div class="tg-card-header">
                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:{{ $project->type === 'laravel' ? 'rgba(255,45,85,0.12)' : ($project->type === 'python' ? 'rgba(55,139,255,0.12)' : 'var(--tg-accent-dim)') }}">
                    <i class="fab {{ $project->type === 'laravel' ? 'fa-laravel' : ($project->type === 'python' ? 'fa-python' : 'fa-code') }}"
                       style="color:{{ $project->type === 'laravel' ? '#ff2d55' : ($project->type === 'python' ? '#378bff' : 'var(--tg-accent)') }}"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="tg-card-title" style="font-size:14px">{{ $project->name }}</div>
                    <div style="font-size:11px;color:var(--tg-text-muted);font-family:var(--tg-font-mono)">{{ $project->slug }}</div>
                </div>
                <span class="tg-badge {{ $project->environment === 'production' ? 'badge-critical' : 'badge-low' }}" style="font-size:10px">
                    {{ strtoupper(substr($project->environment,0,4)) }}
                </span>
            </div>
            <div class="tg-card-body">
                @if($project->description)
                <p style="font-size:13px;color:var(--tg-text-muted);margin-bottom:16px;line-height:1.5">
                    {{ Str::limit($project->description, 80) }}
                </p>
                @endif

                {{-- Risk Score --}}
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:11px;color:var(--tg-text-muted);text-transform:uppercase;letter-spacing:0.8px">Risk Score</span>
                    <span style="font-size:13px;font-weight:700;font-family:var(--tg-font-mono);color:{{ $project->risk_score > 75 ? 'var(--tg-critical)' : ($project->risk_score > 40 ? 'var(--tg-medium)' : 'var(--tg-accent)') }}">
                        {{ $project->risk_score }}/100
                    </span>
                </div>
                <div class="tg-progress mb-3">
                    <div class="tg-progress-bar {{ $project->risk_score > 75 ? 'critical' : ($project->risk_score > 40 ? 'medium' : 'accent') }}"
                         style="width:{{ $project->risk_score }}%"></div>
                </div>

                {{-- Stats row --}}
                <div class="d-flex justify-content-between" style="padding-top:12px;border-top:1px solid var(--tg-border)">
                    <div class="text-center">
                        <div style="font-family:var(--tg-font-display);font-size:20px;font-weight:700;color:var(--tg-critical)">
                            {{ $project->servers->count() }}
                        </div>
                        <div style="font-size:10px;color:var(--tg-text-muted);text-transform:uppercase;letter-spacing:0.8px">Servers</div>
                    </div>
                    <div class="text-center">
                        <div style="font-family:var(--tg-font-display);font-size:20px;font-weight:700;color:var(--tg-accent)">
                            {{ $project->servers->where('status','online')->count() }}
                        </div>
                        <div style="font-size:10px;color:var(--tg-text-muted);text-transform:uppercase;letter-spacing:0.8px">Online</div>
                    </div>
                    <div class="text-center">
                        <div style="font-family:var(--tg-font-display);font-size:20px;font-weight:700;color:{{ $project->status === 'active' ? 'var(--tg-accent)' : 'var(--tg-critical)' }}">
                            <i class="fas {{ $project->status === 'active' ? 'fa-circle-check' : 'fa-circle-xmark' }}" style="font-size:18px"></i>
                        </div>
                        <div style="font-size:10px;color:var(--tg-text-muted);text-transform:uppercase;letter-spacing:0.8px">{{ ucfirst($project->status) }}</div>
                    </div>
                    <div class="text-center">
                        <div style="font-size:10px;color:var(--tg-text-muted);margin-bottom:2px;text-transform:uppercase;letter-spacing:0.8px">Last Scan</div>
                        <div style="font-size:11px;color:var(--tg-text-secondary)">
                            {{ $project->last_scan_at?->diffForHumans() ?? 'Never' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="tg-card">
            <div class="tg-card-body text-center py-5">
                <i class="fas fa-folder-plus" style="font-size:52px;color:var(--tg-accent);opacity:0.3;display:block;margin-bottom:18px"></i>
                <div style="font-family:var(--tg-font-display);font-size:20px;font-weight:600;margin-bottom:8px">No projects yet</div>
                <div style="color:var(--tg-text-muted);font-size:14px;margin-bottom:24px">Create your first project and connect the Torex Guard agent</div>
                <a href="{{ route('projects.create') }}" class="btn-tg-primary">
                    <i class="fas fa-plus"></i> Create First Project
                </a>
            </div>
        </div>
    </div>
    @endforelse
</div>

@if($projects->hasPages())
<div class="mt-4">{{ $projects->links('pagination::bootstrap-5') }}</div>
@endif
@endsection
