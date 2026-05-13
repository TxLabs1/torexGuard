{{-- settings/profile.blade.php --}}
@extends('layouts.app')
@section('title','Profile Settings')
@section('page-title','Settings')

@section('content')
<div class="row g-3">
    {{-- Sidebar nav --}}
    <div class="col-lg-3">
        <div class="tg-card">
            <div class="tg-card-body" style="padding:12px">
                @foreach([
                    ['route'=>'settings.profile','icon'=>'fa-user','label'=>'Profile'],
                    ['route'=>'2fa.show','icon'=>'fa-shield-halved','label'=>'Two-Factor Auth'],
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
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-user text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Profile Information</div>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('settings.profile.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="tg-form-label">Full Name</label>
                            <input type="text" name="name" class="tg-form-control" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div style="font-size:12px;color:var(--tg-critical);margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="tg-form-label">Email Address</label>
                            <input type="email" name="email" class="tg-form-control" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div style="font-size:12px;color:var(--tg-critical);margin-top:4px">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="tg-form-label">Timezone</label>
                            <select name="timezone" class="tg-select">
                                @foreach(\DateTimeZone::listIdentifiers() as $tz)
                                <option value="{{ $tz }}" {{ $user->timezone == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="tg-form-label">Role</label>
                            <div class="tg-form-control" style="opacity:0.7;cursor:default">{{ ucfirst($user->role) }}</div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-tg-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="tg-card mt-3">
            <div class="tg-card-header">
                <i class="fas fa-lock text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Change Password</div>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('settings.profile.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="tg-form-label">Current Password</label>
                            <input type="password" name="current_password" class="tg-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-4">
                            <label class="tg-form-label">New Password</label>
                            <input type="password" name="password" class="tg-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-4">
                            <label class="tg-form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="tg-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-tg-primary">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
