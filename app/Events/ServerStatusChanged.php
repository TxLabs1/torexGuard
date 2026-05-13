<?php

namespace App\Events;

use App\Models\Server;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Server $server, public string $previous, public string $current) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('org.'.$this->server->organization_id)];
    }

    public function broadcastAs(): string
    {
        return 'server.status';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->server->id,
            'hostname' => $this->server->hostname,
            'previous' => $this->previous,
            'current'  => $this->current,
            'at'       => now()->toIso8601String(),
        ];
    }
}
