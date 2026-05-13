<?php

namespace App\Console\Commands;

use App\Events\ServerStatusChanged;
use App\Models\Server;
use App\Notifications\ServerOfflineNotification;
use App\Support\OrganizationContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Mark servers offline that haven't sent a heartbeat in
 * the configured threshold. Designed to be run every minute by
 * the scheduler.
 */
class HeartbeatStaleSweep extends Command
{
    protected $signature = 'torex:heartbeat-sweep
        {--threshold=180 : Seconds after last_heartbeat_at to consider a server offline}';

    protected $description = 'Mark servers offline when their heartbeat has gone stale.';

    public function handle(): int
    {
        OrganizationContext::asSystem();

        $threshold = (int) $this->option('threshold');
        $cutoff = now()->subSeconds($threshold);

        $marked = 0;

        Server::query()
            ->where('status', '!=', 'offline')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_heartbeat_at')
                    ->orWhere('last_heartbeat_at', '<', $cutoff);
            })
            ->chunkById(200, function ($servers) use (&$marked) {
                foreach ($servers as $server) {
                    $previous = $server->status;
                    $server->forceFill(['status' => 'offline'])->save();
                    broadcast(new ServerStatusChanged($server, $previous, 'offline'));

                    $recipients = $server->organization?->users()->get() ?? collect();
                    if ($recipients->isNotEmpty()) {
                        Notification::send($recipients, new ServerOfflineNotification($server));
                    }

                    $marked++;
                }
            });

        $this->info("heartbeat sweep marked {$marked} server(s) offline");

        return self::SUCCESS;
    }
}
