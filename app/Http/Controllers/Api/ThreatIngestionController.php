<?php

namespace App\Http\Controllers\Api;

use App\Events\AlertCreated;
use App\Events\ThreatDetected;
use App\Models\Alert;
use App\Models\Project;
use App\Models\Threat;
use App\Notifications\ThreatDetectedNotification;
use App\Services\GeoIpService;
use App\Services\ThreatResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ThreatIngestionController
{
    public function __construct(
        private ThreatResponseService $responseService,
        private GeoIpService $geoip,
    ) {}

    private function geo(?string $ip): array
    {
        $g = $this->geoip->lookup($ip);
        return [
            'source_country' => $g['country_code'] ?? null,
        ];
    }

    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'                 => 'required|string|max:64',
            'severity'             => 'required|in:low,medium,high,critical',
            'title'                => 'required|string|max:255',
            'description'          => 'nullable|string|max:5000',
            'file_path'            => 'nullable|string|max:1024',
            'file_hash'            => 'nullable|string|max:128',
            'file_size'            => 'nullable|integer|min:0',
            'source_ip'            => 'nullable|ip',
            'confidence_score'     => 'nullable|integer|between:0,100',
            'detection_details'    => 'nullable|array',
            'signatures_matched'   => 'nullable|array',
            'signatures_matched.*' => 'string|max:255',
            'detected_at'          => 'nullable|date',
        ]);

        $agent = $request->attributes->get('agent');

        $threat = Threat::create([
            'type'              => $validated['type'],
            'severity'          => $validated['severity'],
            'title'             => $validated['title'],
            'description'       => $validated['description']        ?? null,
            'file_path'         => $validated['file_path']          ?? null,
            'file_hash'         => $validated['file_hash']          ?? null,
            'file_size'         => $validated['file_size']          ?? null,
            'source_ip'         => $validated['source_ip']          ?? null,
            'confidence_score'  => $validated['confidence_score']   ?? 0,
            'detection_details' => $validated['detection_details']  ?? null,
            'signatures_matched'=> $validated['signatures_matched'] ?? null,
            'threat_id'         => Threat::generateThreatId(),
            'project_id'        => $agent->project_id,
            'server_id'         => $agent->server_id,
            'organization_id'   => $agent->project->organization_id,
            'status'            => 'active',
            'detected_at'       => $validated['detected_at'] ?? now(),
            ...$this->geo($validated['source_ip'] ?? null),
        ]);

        $alert = Alert::create([
            'project_id'      => $agent->project_id,
            'threat_id'       => $threat->id,
            'organization_id' => $agent->project->organization_id,
            'title'           => "New {$threat->severity} threat: {$threat->title}",
            'message'         => $threat->description ?? "Detected on {$agent->server->hostname}",
            'severity'        => $threat->severity,
        ]);

        if ($threat->severity === 'critical' && ($agent->project->settings['auto_quarantine'] ?? false)) {
            $this->responseService->quarantine($threat);
        }

        $this->updateRiskScore($agent->project_id);

        broadcast(new ThreatDetected($threat))->toOthers();
        broadcast(new AlertCreated($alert))->toOthers();

        $recipients = $agent->project->organization?->users()->get() ?? collect();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ThreatDetectedNotification($threat));
        }

        return response()->json([
            'status'    => 'received',
            'threat_id' => $threat->threat_id,
            'actions'   => $threat->auto_remediated ? [$threat->remediation_action] : [],
        ], 201);
    }

    public function reportBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'threats'                          => 'required|array|max:100',
            'threats.*.type'                   => 'required|string|max:64',
            'threats.*.severity'               => 'required|in:low,medium,high,critical',
            'threats.*.title'                  => 'required|string|max:255',
            'threats.*.description'            => 'nullable|string|max:5000',
            'threats.*.file_path'              => 'nullable|string|max:1024',
            'threats.*.file_hash'              => 'nullable|string|max:128',
            'threats.*.file_size'              => 'nullable|integer|min:0',
            'threats.*.source_ip'              => 'nullable|ip',
            'threats.*.confidence_score'       => 'nullable|integer|between:0,100',
            'threats.*.detection_details'      => 'nullable|array',
            'threats.*.signatures_matched'     => 'nullable|array',
            'threats.*.signatures_matched.*'   => 'string|max:255',
            'threats.*.detected_at'            => 'nullable|date',
        ]);

        $agent   = $request->attributes->get('agent');
        $results = [];

        DB::transaction(function () use ($validated, $agent, &$results) {
            foreach ($validated['threats'] as $row) {
                $threat = Threat::create([
                    'type'              => $row['type'],
                    'severity'          => $row['severity'],
                    'title'             => $row['title'],
                    'description'       => $row['description']         ?? null,
                    'file_path'         => $row['file_path']           ?? null,
                    'file_hash'         => $row['file_hash']           ?? null,
                    'file_size'         => $row['file_size']           ?? null,
                    'source_ip'         => $row['source_ip']           ?? null,
                    'confidence_score'  => $row['confidence_score']    ?? 0,
                    'detection_details' => $row['detection_details']   ?? null,
                    'signatures_matched'=> $row['signatures_matched']  ?? null,
                    'threat_id'         => Threat::generateThreatId(),
                    'project_id'        => $agent->project_id,
                    'server_id'         => $agent->server_id,
                    'organization_id'   => $agent->project->organization_id,
                    'status'            => 'active',
                    'detected_at'       => $row['detected_at'] ?? now(),
                    ...$this->geo($row['source_ip'] ?? null),
                ]);
                $results[] = $threat->threat_id;
            }
        });

        $this->updateRiskScore($agent->project_id);

        return response()->json([
            'status'     => 'received',
            'count'      => count($results),
            'threat_ids' => $results,
        ]);
    }

    private function updateRiskScore(int $projectId): void
    {
        $project = Project::find($projectId);
        $criticalCount = Threat::where('project_id', $projectId)->where('severity', 'critical')->where('status', 'active')->count();
        $highCount     = Threat::where('project_id', $projectId)->where('severity', 'high')->where('status', 'active')->count();
        $score = min(100, ($criticalCount * 15) + ($highCount * 5));
        $project->update(['risk_score' => $score]);
    }
}
