<?php

namespace App\Http\Controllers\Api;

use App\Models\Alert;
use App\Models\Server;
use App\Models\Threat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * All queries here rely on the OrganizationScope global scope, so the
 * `organization_id` filter is applied automatically based on the
 * authenticated user.
 */
class DashboardApiController
{
    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'critical'       => Threat::query()->where('severity', 'critical')->where('status', 'active')->count(),
            'total_active'   => Threat::query()->whereIn('status', ['active', 'investigating'])->count(),
            'servers_online' => Server::query()->where('status', 'online')->count(),
        ]);
    }

    public function recentThreats(Request $request): JsonResponse
    {
        $after = (int) $request->query('after', 0);

        $threats = Threat::with('server')
            ->where('id', '>', $after)
            ->latest('detected_at')
            ->take(10)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'severity' => $t->severity,
                'type'     => $t->type,
                'server'   => $t->server?->hostname,
                'detected' => $t->detected_at?->toIso8601String(),
            ]);

        return response()->json(['threats' => $threats]);
    }

    public function threatTimeline(Request $request): JsonResponse
    {
        $days = 7;
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $base = Threat::query()->whereDate('detected_at', $date);

            $data[] = [
                'date'     => $date->format('M d'),
                'critical' => (clone $base)->where('severity', 'critical')->count(),
                'high'     => (clone $base)->where('severity', 'high')->count(),
                'medium'   => (clone $base)->where('severity', 'medium')->count(),
            ];
        }

        return response()->json($data);
    }

    public function serverStatus(Request $request): JsonResponse
    {
        $servers = Server::query()->get()->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'status'       => $s->status,
            'online'       => $s->isOnline(),
            'cpu_usage'    => $s->cpu_usage,
            'memory_usage' => $s->memory_usage,
            'disk_usage'   => $s->disk_usage,
        ]);

        return response()->json(['servers' => $servers]);
    }

    public function unreadAlerts(Request $request): JsonResponse
    {
        return response()->json([
            'count' => Alert::query()->where('is_read', false)->count(),
        ]);
    }

    public function eventStream(Request $request): JsonResponse
    {
        $after = (int) $request->query('after', 0);

        $threats = Threat::with('server')
            ->where('id', '>', $after)
            ->latest('detected_at')
            ->take(20)
            ->get();

        return response()->json([
            'cursor' => $threats->max('id') ?? $after,
            'events' => $threats->map(fn ($t) => [
                'id'       => $t->id,
                'type'     => 'threat',
                'severity' => $t->severity,
                'title'    => $t->title,
                'server'   => $t->server?->hostname,
                'at'       => $t->detected_at?->toIso8601String(),
            ]),
        ]);
    }
}
