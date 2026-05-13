<?php

namespace App\Jobs;

use App\Events\ScanCompleted;
use App\Models\ScanResult;
use App\Support\OrganizationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanCompletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private int $scanId)
    {
        $this->onQueue('events');
    }

    public function handle(): void
    {
        OrganizationContext::asSystem();

        $scan = ScanResult::with(['project', 'server'])->find($this->scanId);
        if (! $scan) {
            return;
        }

        $scan->project?->forceFill(['last_scan_at' => $scan->completed_at ?? now()])->save();

        broadcast(new ScanCompleted($scan));
    }
}
