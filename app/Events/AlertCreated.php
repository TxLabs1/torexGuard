<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alert $alert) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.'.$this->alert->organization_id)];
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->alert->id,
            'title'    => $this->alert->title,
            'severity' => $this->alert->severity,
            'message'  => $this->alert->message,
            'at'       => $this->alert->created_at->toIso8601String(),
        ];
    }
}
