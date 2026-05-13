<?php

namespace App\Http\Controllers\Api;

use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController
{
    public function receive(Request $request): JsonResponse
    {
        $agent = $request->attributes->get('agent');

        $agent->update(['last_heartbeat_at' => now(), 'status' => 'active']);

        $server = $agent->server;
        $server->update([
            'last_heartbeat_at' => now(),
            'status'            => 'online',
            'cpu_usage'         => $request->cpu_usage ?? $server->cpu_usage,
            'memory_usage'      => $request->memory_usage ?? $server->memory_usage,
            'disk_usage'        => $request->disk_usage ?? $server->disk_usage,
        ]);

        if ($request->cpu_usage > 90) {
            Alert::create([
                'project_id'      => $agent->project_id,
                'organization_id' => $agent->project->organization_id,
                'title'           => 'High CPU Usage Detected',
                'message'         => "Server {$server->name} CPU at {$request->cpu_usage}%",
                'severity'        => 'high',
            ]);
        }

        return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
    }
}
