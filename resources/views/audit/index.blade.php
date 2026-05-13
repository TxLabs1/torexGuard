@extends('layouts.app')
@section('title','Audit Log')
@section('page-title','Audit Log')

@section('content')
<div class="tg-card mb-3">
    <div class="tg-card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="tg-form-label">Search</label>
                <input class="tg-form-control" name="search" value="{{ request('search') }}" placeholder="description or subject type">
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">Log</label>
                <select class="tg-form-control" name="log_name">
                    <option value="">All</option>
                    @foreach($logNames as $name)
                        <option value="{{ $name }}" {{ request('log_name')===$name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">Event</label>
                <select class="tg-form-control" name="event">
                    <option value="">All</option>
                    @foreach(['created','updated','deleted'] as $ev)
                        <option value="{{ $ev }}" {{ request('event')===$ev ? 'selected' : '' }}>{{ ucfirst($ev) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">From</label>
                <input type="date" name="from" class="tg-form-control" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="tg-form-label">To</label>
                <input type="date" name="to" class="tg-form-control" value="{{ request('to') }}">
            </div>
            <div class="col-md-1">
                <button class="btn-tg-primary" style="width:100%;padding:8px 0">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="tg-card">
    <table class="tg-table">
        <thead>
            <tr>
                <th>WHEN</th>
                <th>WHO</th>
                <th>EVENT</th>
                <th>SUBJECT</th>
                <th>DESCRIPTION</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $a)
                <tr>
                    <td style="font-family:var(--tg-font-mono);font-size:12px;color:var(--tg-text-muted)">
                        {{ $a->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <td>{{ $a->causer?->name ?? 'system' }}</td>
                    <td><span class="tg-badge">{{ $a->event ?? $a->log_name }}</span></td>
                    <td style="font-family:var(--tg-font-mono);font-size:12px">
                        {{ class_basename($a->subject_type) }}#{{ $a->subject_id }}
                    </td>
                    <td>{{ $a->description }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--tg-text-muted)">No activity matched the filters.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($activities->hasPages())
        <div class="tg-card-body">{{ $activities->links() }}</div>
    @endif
</div>
@endsection
