<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Server;
use App\Models\Threat;
use App\Notifications\WeeklyDigestNotification;
use App\Support\OrganizationContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendWeeklyDigest extends Command
{
    protected $signature = 'torex:weekly-digest';

    protected $description = 'Send the weekly Torex Guard digest to every organization that has opted in.';

    public function handle(): int
    {
        OrganizationContext::asSystem();

        $from = now()->subWeek();
        $to   = now();

        Organization::with('users', 'notificationSetting')->chunkById(50, function ($orgs) use ($from, $to) {
            foreach ($orgs as $org) {
                $settings = $org->notificationSetting;
                if ($settings && ! in_array('weekly_digest', $settings->notify_on ?? ['weekly_digest'], true)) {
                    continue;
                }

                $threats   = Threat::where('organization_id', $org->id)->whereBetween('detected_at', [$from, $to]);
                $stats = [
                    'from'             => $from->toDateString(),
                    'to'               => $to->toDateString(),
                    'threats'          => (clone $threats)->count(),
                    'critical'         => (clone $threats)->where('severity', 'critical')->count(),
                    'active'           => Threat::where('organization_id', $org->id)->where('status', 'active')->count(),
                    'resolved'         => (clone $threats)->where('status', 'resolved')->count(),
                    'servers_online'   => Server::where('organization_id', $org->id)->where('status', 'online')->count(),
                    'servers_offline'  => Server::where('organization_id', $org->id)->where('status', 'offline')->count(),
                ];

                if ($org->users->isNotEmpty()) {
                    Notification::send($org->users, new WeeklyDigestNotification($stats));
                }
            }
        });

        return self::SUCCESS;
    }
}
