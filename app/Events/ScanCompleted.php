<?php

namespace App\Events;

use App\Models\ScanResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScanCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ScanResult $scan) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.'.$this->scan->project->organization_id)];
    }

    public function broadcastAs(): string
    {
        return 'scan.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->scan->id,
            'scan_id' => $this->scan->scan_id,
            'project' => $this->scan->project?->name,
            'server' => $this->scan->server?->hostname,
            'files_scanned' => $this->scan->files_scanned,
            'threats_found' => $this->scan->threats_found,
            'duration' => $this->scan->duration_seconds,
            'completed_at' => $this->scan->completed_at?->toIso8601String(),
        ];
    }
}
