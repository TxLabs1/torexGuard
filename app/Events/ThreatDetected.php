<?php

namespace App\Events;

use App\Models\Threat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThreatDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Threat $threat) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.'.$this->threat->organization_id)];
    }

    public function broadcastAs(): string
    {
        return 'threat.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->threat->id,
            'threat_id' => $this->threat->threat_id,
            'severity'  => $this->threat->severity,
            'type'      => $this->threat->type,
            'title'     => $this->threat->title,
            'server'    => $this->threat->server?->hostname,
            'project'   => $this->threat->project?->name,
            'detected'  => $this->threat->detected_at?->toIso8601String(),
        ];
    }
}
