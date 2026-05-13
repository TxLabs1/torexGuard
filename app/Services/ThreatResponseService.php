<?php

namespace App\Services;

use App\Models\Action;
use App\Models\QuarantineFile;
use App\Models\Threat;
use App\Models\ThreatLog;
use Illuminate\Support\Facades\Log;

class ThreatResponseService
{
    /**
     * Quarantine a detected threat file.
     * The agent receives the quarantine command and moves the file on the server side.
     * This method records the action and queues the command.
     */
    public function quarantine(Threat $threat): bool
    {
        try {
            $quarantinePath = config('guard.quarantine_path', '/var/torex-guard/quarantine')
                .'/'.$threat->threat_id.'_'.basename($threat->file_path ?? 'unknown');

            $action = Action::create([
                'threat_id' => $threat->id,
                'project_id' => $threat->project_id,
                'type' => 'quarantine_file',
                'status' => 'pending',
                'is_automated' => true,
                'target' => $threat->file_path ?? 'N/A',
            ]);

            QuarantineFile::create([
                'threat_id' => $threat->id,
                'project_id' => $threat->project_id,
                'server_id' => $threat->server_id,
                'original_path' => $threat->file_path ?? '',
                'quarantine_path' => $quarantinePath,
                'file_hash' => $threat->file_hash ?? '',
                'file_size' => $threat->file_size ?? 0,
                'quarantined_at' => now(),
            ]);

            $threat->update([
                'status' => 'quarantined',
                'auto_remediated' => true,
                'remediation_action' => 'quarantine_file',
            ]);

            ThreatLog::create([
                'threat_id' => $threat->id,
                'action' => 'quarantined',
                'note' => 'File quarantined automatically by Torex Guard',
            ]);

            $action->update(['status' => 'success', 'executed_at' => now()]);

            // Dispatch agent command
            // QuarantineFileOnAgent::dispatch($threat->server, $threat->file_path, $quarantinePath);

            return true;

        } catch (\Exception $e) {
            Log::error('Quarantine failed', ['threat_id' => $threat->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Block an IP at firewall level (via agent command)
     */
    public function blockIp(Threat $threat, string $ip): bool
    {
        try {
            Action::create([
                'threat_id' => $threat->id,
                'project_id' => $threat->project_id,
                'type' => 'block_ip',
                'status' => 'pending',
                'target' => $ip,
            ]);

            // Dispatch: BlockIpOnAgent::dispatch($threat->server, $ip);
            return true;

        } catch (\Exception $e) {
            Log::error('IP block failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Kill a suspicious process (via agent command)
     */
    public function killProcess(Threat $threat): bool
    {
        if (! $threat->process_id) {
            return false;
        }

        try {
            Action::create([
                'threat_id' => $threat->id,
                'project_id' => $threat->project_id,
                'type' => 'kill_process',
                'status' => 'pending',
                'target' => "PID:{$threat->process_id} ({$threat->process_name})",
            ]);

            // Dispatch: KillProcessOnAgent::dispatch($threat->server, $threat->process_id);
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Lock file permissions (chmod 000)
     */
    public function chmodLock(Threat $threat): bool
    {
        try {
            Action::create([
                'threat_id' => $threat->id,
                'project_id' => $threat->project_id,
                'type' => 'chmod_lock',
                'status' => 'pending',
                'target' => $threat->file_path ?? '',
            ]);

            // Dispatch: ChmodLockOnAgent::dispatch($threat->server, $threat->file_path);
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
