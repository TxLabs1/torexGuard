<?php

namespace App\Jobs;

use App\Events\ThreatDetected;
use App\Models\Threat;
use App\Models\ThreatEvent;
use App\Notifications\ThreatDetectedNotification;
use App\Services\ThreatResponseService;
use App\Support\OrganizationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class ProcessThreatEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(private ThreatEvent $event)
    {
        $this->onQueue('events');
    }

    public function handle(ThreatResponseService $responseService): void
    {
        OrganizationContext::asSystem();
        $payload = $this->event->payload;

        // Analyze and auto-elevate to threat if needed
        if ($this->isThreatIndicator($this->event->type, $this->event->severity)) {
            $existingThreat = Threat::where('project_id', $this->event->project_id)
                ->where('server_id', $this->event->server_id)
                ->where('type', $this->mapEventTypeToThreatType($this->event->type))
                ->where('status', 'active')
                ->where('detected_at', '>=', now()->subMinutes(5))
                ->first();

            if (! $existingThreat) {
                $threat = Threat::create([
                    'threat_id' => Threat::generateThreatId(),
                    'project_id' => $this->event->project_id,
                    'server_id' => $this->event->server_id,
                    'organization_id' => $this->event->project->organization_id,
                    'type' => $this->mapEventTypeToThreatType($this->event->type),
                    'severity' => $this->event->severity === 'critical' ? 'critical' : 'high',
                    'title' => $this->buildThreatTitle($this->event->type, $payload),
                    'description' => 'Auto-escalated from event: '.$this->event->event_id,
                    'file_path' => $payload['file_path'] ?? null,
                    'source_ip' => $payload['source_ip'] ?? null,
                    'confidence_score' => 75,
                    'status' => 'active',
                    'detected_at' => $this->event->occurred_at,
                ]);

                broadcast(new ThreatDetected($threat));

                if ($threat->severity === 'critical'
                    && ($this->event->project->settings['auto_quarantine'] ?? false)
                    && $threat->file_path) {
                    $responseService->quarantine($threat);
                }

                $recipients = $this->event->project->organization?->users()->get() ?? collect();
                if ($recipients->isNotEmpty()) {
                    Notification::send($recipients, new ThreatDetectedNotification($threat));
                }
            }
        }

        $this->event->update(['processed' => true]);
    }

    private function isThreatIndicator(string $type, string $severity): bool
    {
        $threatEvents = ['file_created', 'suspicious_process', 'outbound_connection', 'privilege_escalation'];

        return in_array($type, $threatEvents) && in_array($severity, ['high', 'critical']);
    }

    private function mapEventTypeToThreatType(string $eventType): string
    {
        return match ($eventType) {
            'file_created', 'file_modified' => 'file_modification',
            'suspicious_process' => 'suspicious_process',
            'outbound_connection' => 'outbound_connection',
            'privilege_escalation' => 'privilege_escalation',
            default => 'other',
        };
    }

    private function buildThreatTitle(string $type, array $payload): string
    {
        return match ($type) {
            'file_created' => 'Suspicious file created: '.basename($payload['file_path'] ?? 'unknown'),
            'suspicious_process' => 'Suspicious process: '.($payload['process_name'] ?? 'unknown'),
            'outbound_connection' => 'Suspicious outbound connection to '.($payload['destination'] ?? 'unknown'),
            'privilege_escalation' => 'Privilege escalation attempt detected',
            default => 'Security event: '.str_replace('_', ' ', $type),
        };
    }
}
