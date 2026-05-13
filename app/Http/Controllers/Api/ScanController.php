<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ScanCompletedJob;
use App\Models\ScanResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScanController
{
    public function start(Request $request): JsonResponse
    {
        $agent = $request->attributes->get('agent');

        $scan = ScanResult::create([
            'project_id' => $agent->project_id,
            'server_id' => $agent->server_id,
            'scan_id' => 'scan_'.Str::random(16),
            'type' => $request->input('type', 'full'),
            'status' => 'running',
            'started_at' => now(),
        ]);

        return response()->json([
            'status' => 'started',
            'scan_id' => $scan->scan_id,
        ], 201);
    }

    public function update(Request $request, string $scanId): JsonResponse
    {
        $scan = ScanResult::where('scan_id', $scanId)->firstOrFail();

        $scan->update($request->only([
            'files_scanned',
            'threats_found',
            'files_clean',
        ]));

        return response()->json(['status' => 'updated']);
    }

    public function complete(Request $request, string $scanId): JsonResponse
    {
        $scan = ScanResult::where('scan_id', $scanId)->firstOrFail();

        $scan->update([
            'status' => $request->input('status', 'completed'),
            'files_scanned' => $request->input('files_scanned', $scan->files_scanned),
            'threats_found' => $request->input('threats_found', $scan->threats_found),
            'files_clean' => $request->input('files_clean', $scan->files_clean),
            'duration_seconds' => $request->input('duration_seconds'),
            'summary' => $request->input('summary'),
            'completed_at' => now(),
        ]);

        ScanCompletedJob::dispatch($scan->id);

        return response()->json([
            'status' => 'completed',
            'scan_id' => $scan->scan_id,
        ]);
    }
}
