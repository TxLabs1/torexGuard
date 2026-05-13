<?php

namespace App\Http\Controllers\Api;

use App\Models\FileSnapshot;
use App\Models\Threat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FileIntegrityController
{
    public function submitSnapshot(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array',
            'files.*.path' => 'required|string',
            'files.*.hash' => 'required|string',
            'files.*.size' => 'required|integer',
            'files.*.permissions' => 'required|string',
        ]);

        $agent = $request->attributes->get('agent');
        $inserts = [];

        foreach ($request->files as $file) {
            $inserts[] = [
                'project_id'       => $agent->project_id,
                'server_id'        => $agent->server_id,
                'file_path'        => $file['path'],
                'file_hash'        => $file['hash'],
                'file_size'        => $file['size'],
                'permissions'      => $file['permissions'],
                'owner'            => $file['owner'] ?? null,
                'file_modified_at' => $file['modified_at'] ?? now(),
                'is_baseline'      => true,
                'change_type'      => 'baseline',
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        FileSnapshot::insert($inserts);

        return response()->json(['status' => 'snapshot_saved', 'count' => count($inserts)]);
    }

    public function reportChanges(Request $request): JsonResponse
    {
        $request->validate(['changes' => 'required|array']);
        $agent = $request->attributes->get('agent');
        $flagged = 0;

        foreach ($request->changes as $change) {
            $snapshot = FileSnapshot::create([
                'project_id'  => $agent->project_id,
                'server_id'   => $agent->server_id,
                'file_path'   => $change['path'],
                'file_hash'   => $change['hash'] ?? '',
                'file_size'   => $change['size'] ?? 0,
                'permissions' => $change['permissions'] ?? '000',
                'change_type' => $change['type'],
                'flagged'     => $change['suspicious'] ?? false,
            ]);

            if ($snapshot->flagged) {
                $flagged++;
                Threat::create([
                    'threat_id'       => Threat::generateThreatId(),
                    'project_id'      => $agent->project_id,
                    'server_id'       => $agent->server_id,
                    'organization_id' => $agent->project->organization_id,
                    'type'            => 'file_modification',
                    'severity'        => 'medium',
                    'title'           => 'Suspicious File Change: ' . basename($change['path']),
                    'file_path'       => $change['path'],
                    'status'          => 'active',
                    'detected_at'     => now(),
                    'confidence_score'=> 70,
                ]);
            }
        }

        return response()->json(['status' => 'changes_recorded', 'flagged' => $flagged]);
    }
}
