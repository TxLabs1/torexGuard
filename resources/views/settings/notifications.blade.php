{{-- resources/views/settings/notifications.blade.php --}}
@extends('layouts.app')
@section('title','Notifications')
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
        <div class="tg-card">
            <div class="tg-card-header">
                <i class="fas fa-bell text-accent" style="font-size:13px"></i>
                <div class="tg-card-title">Notification Channels</div>
            </div>
            <div class="tg-card-body">
                <form method="POST" action="{{ route('settings.notifications.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-4">
                        {{-- Email --}}
                        <div class="col-12">
                            <div style="background:rgba(255,255,255,0.02);border:1px solid var(--tg-border);border-radius:12px;padding:20px">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-10">
                                        <i class="fas fa-envelope text-accent me-3"></i>
                                        <div>
                                            <div style="font-weight:600;font-size:14px">Email Alerts</div>
                                            <div style="font-size:12px;color:var(--tg-text-muted)">Receive threat alerts via email</div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="email_enabled" value="1"
                                               {{ $settings->email_enabled ?? true ? 'checked' : '' }}
                                               style="background-color:var(--tg-accent);border-color:var(--tg-accent)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Slack --}}
                        <div class="col-12">
                            <div style="background:rgba(255,255,255,0.02);border:1px solid var(--tg-border);border-radius:12px;padding:20px">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-10">
                                        <i class="fab fa-slack text-accent me-3" style="font-size:18px"></i>
                                        <div>
                                            <div style="font-weight:600;font-size:14px">Slack Notifications</div>
                                            <div style="font-size:12px;color:var(--tg-text-muted)">Send alerts to a Slack channel</div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="slack_enabled" value="1"
                                               {{ $settings->slack_enabled ?? false ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div>
                                    <label class="tg-form-label">Slack Webhook URL</label>
                                    <input type="url" name="slack_webhook" class="tg-form-control"
                                           placeholder="https://hooks.slack.com/services/..."
                                           value="{{ $settings->slack_webhook ?? '' }}">
                                </div>
                            </div>
                        </div>

                        {{-- Webhook --}}
                        <div class="col-12">
                            <div style="background:rgba(255,255,255,0.02);border:1px solid var(--tg-border);border-radius:12px;padding:20px">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-10">
                                        <i class="fas fa-webhook text-accent me-3"></i>
                                        <div>
                                            <div style="font-weight:600;font-size:14px">Custom Webhook</div>
                                            <div style="font-size:12px;color:var(--tg-text-muted)">POST threat events to your endpoint</div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="webhook_enabled" value="1"
                                               {{ $settings->webhook_enabled ?? false ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div>
                                    <label class="tg-form-label">Webhook URL</label>
                                    <input type="url" name="webhook_url" class="tg-form-control"
                                           placeholder="https://yourapp.com/webhooks/torex-guard"
                                           value="{{ $settings->webhook_url ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn-tg-primary">
                                <i class="fas fa-save"></i> Save Notification Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
