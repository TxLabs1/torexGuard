<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessThreatEvent;
use App\Models\ThreatEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController
{
    public function ingest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:64',
            'severity' => 'nullable|in:info,low,medium,high,critical',
            'payload' => 'required|array',
            'occurred_at' => 'nullable|date',
        ]);

        $agent = $request->attributes->get('agent');

        $event = ThreatEvent::create([
            'event_id' => 'evt_'.Str::random(16),
            'project_id' => $agent->project_id,
            'server_id' => $agent->server_id,
            'type' => $validated['type'],
            'severity' => $validated['severity'] ?? 'info',
            'payload' => $validated['payload'],
            'occurred_at' => $validated['occurred_at'] ?? now(),
        ]);

        ProcessThreatEvent::dispatch($event);

        return response()->json(['status' => 'queued', 'event_id' => $event->event_id]);
    }

    public function ingestBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => 'required|array|max:500',
            'events.*.type' => 'required|string|max:64',
            'events.*.severity' => 'nullable|in:info,low,medium,high,critical',
            'events.*.payload' => 'required|array',
            'events.*.occurred_at' => 'nullable|date',
        ]);

        $agent = $request->attributes->get('agent');

        $created = [];
        foreach ($validated['events'] as $e) {
            $created[] = ThreatEvent::create([
                'event_id' => 'evt_'.Str::random(16),
                'project_id' => $agent->project_id,
                'server_id' => $agent->server_id,
                'type' => $e['type'],
                'severity' => $e['severity'] ?? 'info',
                'payload' => $e['payload'],
                'occurred_at' => $e['occurred_at'] ?? now(),
            ]);
        }

        foreach ($created as $event) {
            ProcessThreatEvent::dispatch($event);
        }

        return response()->json(['status' => 'queued', 'count' => count($created)]);
    }
}
