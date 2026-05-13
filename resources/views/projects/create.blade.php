{{-- resources/views/projects/create.blade.php --}}
@extends('layouts.app')
@section('title','New Project')
@section('page-title','New Project')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('projects.index') }}" class="btn-tg-ghost" style="padding:7px 14px;font-size:13px">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-folder-plus text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Create New Project</div>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('projects.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="tg-form-label">Project Name <span style="color:var(--tg-critical)">*</span></label>
                            <input type="text" name="name" class="tg-form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                   placeholder="e.g. Production API, Main Laravel App" value="{{ old('name') }}" required>
                            @error('name')<div style="font-size:12px;color:var(--tg-critical);margin-top:5px"><i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="tg-form-label">Project Type <span style="color:var(--tg-critical)">*</span></label>
                            <select name="type" class="tg-select" required>
                                <option value="">Select type...</option>
                                <option value="laravel" {{ old('type') == 'laravel' ? 'selected' : '' }}>Laravel (PHP)</option>
                                <option value="python" {{ old('type') == 'python' ? 'selected' : '' }}>Python / Django / FastAPI</option>
                                <option value="node" {{ old('type') == 'node' ? 'selected' : '' }}>Node.js / Express</option>
                                <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('type')<div style="font-size:12px;color:var(--tg-critical);margin-top:5px">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="tg-form-label">Environment <span style="color:var(--tg-critical)">*</span></label>
                            <select name="environment" class="tg-select" required>
                                <option value="production" {{ old('environment','production') == 'production' ? 'selected' : '' }}>Production</option>
                                <option value="staging" {{ old('environment') == 'staging' ? 'selected' : '' }}>Staging</option>
                                <option value="development" {{ old('environment') == 'development' ? 'selected' : '' }}>Development</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="tg-form-label">Description <span style="color:var(--tg-text-muted)">(optional)</span></label>
                            <textarea name="description" class="tg-form-control" rows="3"
                                      placeholder="Brief description of this project...">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div style="background:var(--tg-accent-dim);border:1px solid rgba(0,255,136,0.15);border-radius:12px;padding:16px">
                                <div style="font-size:13px;font-weight:600;color:var(--tg-accent);margin-bottom:8px">
                                    <i class="fas fa-info-circle me-2"></i>After creation
                                </div>
                                <div style="font-size:13px;color:var(--tg-text-secondary);line-height:1.6">
                                    An API key and secret will be generated. Use them to connect the Torex Guard agent to your server.
                                    You can install the agent via our Laravel SDK, Python SDK, or standalone binary.
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <a href="{{ route('projects.index') }}" class="btn-tg-ghost">Cancel</a>
                            <button type="submit" class="btn-tg-primary">
                                <i class="fas fa-shield-plus"></i> Create Project
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
