{{-- resources/views/settings/organization.blade.php --}}
@extends('layouts.app')
@section('title','Organization')
@section('page-title','Settings')

@section('content')
<div class="row g-3">
    <div class="col-lg-3">
        <div class="tg-card">
            <div class="tg-card-body" style="padding:12px">
                @foreach([
                    ['route'=>'settings.profile','icon'=>'fa-user','label'=>'Profile'],
                    ['route'=>'settings.organization','icon'=>'fa-building','label'=>'Organization'],
                    ['route'=>'settings.notifications','icon'=>'fa-bell','label'=>'Notifications'],
                    ['route'=>'settings.api-tokens','icon'=>'fa-key','label'=>'API Tokens'],
                ] as $item)
                <a href="{{ route($item['route']) }}"
                   class="tg-nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                   style="border-radius:10px;margin-bottom:2px">
                    <span class="nav-icon"><i class="fas {{ $item['icon'] }}"></i></span>
                    {{ $item['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="tg-card mb-3">
            <div class="tg-card-header">
                <i class="fas fa-building text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Organization Details</div>
                <span class="tg-badge badge-{{ $org->plan === 'free' ? 'low' : ($org->plan === 'enterprise' ? 'critical' : 'medium') }}" style="font-size:11px">
                    {{ strtoupper($org->plan) }} PLAN
                </span>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('settings.organization.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="tg-form-label">Organization Name</label>
                            <input type="text" name="name" class="tg-form-control" value="{{ old('name', $org->name) }}" required>
                            @error('name')<div style="font-size:12px;color:var(--tg-critical);margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="tg-form-label">Contact Email</label>
                            <input type="email" name="email" class="tg-form-control" value="{{ old('email', $org->email) }}" placeholder="alerts@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="tg-form-label">Website</label>
                            <input type="url" name="website" class="tg-form-control" value="{{ old('website', $org->website) }}" placeholder="https://yourcompany.com">
                        </div>
                        <div class="col-md-6">
                            <label class="tg-form-label">Slug</label>
                            <div class="tg-form-control mono" style="opacity:0.6;cursor:default">{{ $org->slug }}</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-tg-primary"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Plan & Limits --}}
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-gauge text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Plan & Usage</div>
            </div>
            <div class="tg-card-body">
                <div class="row g-3">
                    @foreach([
                        ['label'=>'Servers','used'=>$org->servers()->count(),'max'=>$org->max_servers],
                        ['label'=>'Projects','used'=>$org->projects()->count(),'max'=>$org->max_projects],
                    ] as $usage)
                    <div class="col-md-6">
                        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                            <span style="font-size:13px;color:var(--tg-text-secondary)">{{ $usage['label'] }}</span>
                            <span class="mono" style="font-size:13px;color:var(--tg-text-primary)">{{ $usage['used'] }} / {{ $usage['max'] }}</span>
                        </div>
                        <div class="tg-progress">
                            <div class="tg-progress-bar {{ $usage['used'] >= $usage['max'] ? 'critical' : 'accent' }}"
                                 style="width:{{ $usage['max'] > 0 ? round($usage['used']/$usage['max']*100) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($org->plan === 'free')
                <div style="margin-top:20px;padding:16px;background:rgba(0,212,255,0.06);border:1px solid rgba(0,212,255,0.15);border-radius:12px">
                    <div style="font-size:14px;font-weight:600;color:var(--tg-low,#00d4ff);margin-bottom:6px">
                        <i class="fas fa-arrow-up-right-from-square me-2"></i>Upgrade your plan
                    </div>
                    <div style="font-size:13px;color:var(--tg-text-muted)">
                        Get more servers, unlimited scans, AI threat analysis, and priority support.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
