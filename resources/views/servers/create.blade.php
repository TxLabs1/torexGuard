{{-- resources/views/servers/create.blade.php --}}
@extends('layouts.app')
@section('title','Add Server')
@section('page-title','Add Server')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('servers.index') }}" class="btn-tg-ghost" style="padding:7px 14px;font-size:13px">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-server text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Register New Server</div>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('servers.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="tg-form-label">Project <span style="color:var(--tg-critical)">*</span></label>
                            <select name="project_id" class="tg-select" required>
                                <option value="">Select project...</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }} ({{ $project->environment }})
                                </option>
                                @endforeach
                            </select>
                            @error('project_id')<div style="font-size:12px;color:var(--tg-critical);margin-top:5px">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="tg-form-label">Server Name <span style="color:var(--tg-critical)">*</span></label>
                            <input type="text" name="name" class="tg-form-control" placeholder="e.g. Production Web Server" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="tg-form-label">Hostname <span style="color:var(--tg-critical)">*</span></label>
                            <input type="text" name="hostname" class="tg-form-control" placeholder="e.g. prod-web-01.example.com" value="{{ old('hostname') }}" required>
                        </div>
                        <div class="col-12">
                            <div style="background:var(--tg-accent-dim);border:1px solid rgba(0,255,136,0.15);border-radius:12px;padding:14px;font-size:13px;color:var(--tg-text-secondary)">
                                <i class="fas fa-circle-info text-accent me-2"></i>
                                The agent will auto-register the full system info (OS, IP, CPU, RAM) on first connection.
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <a href="{{ route('servers.index') }}" class="btn-tg-ghost">Cancel</a>
                            <button type="submit" class="btn-tg-primary"><i class="fas fa-server"></i> Register Server</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
