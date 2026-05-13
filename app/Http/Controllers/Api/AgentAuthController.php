<?php

namespace App\Http\Controllers\Api;

use App\Models\Agent;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentAuthController
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_api_key' => 'required|string|max:128',
            'hostname' => 'required|string|max:255',
            'os' => 'required|string|max:64',
            'os_version' => 'nullable|string|max:64',
            'arch' => 'nullable|string|max:32',
            'cpu_count' => 'nullable|integer|min:0|max:65535',
            'memory_total' => 'nullable|integer|min:0',
            'disk_total' => 'nullable|integer|min:0',
            'version' => 'required|string|max:32',
            'ip_address' => 'nullable|ip',
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string|max:64',
        ]);

        $project = Project::findByApiKey($data['project_api_key']);

        if (! $project || $project->status !== 'active') {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $server = Server::firstOrCreate(
            ['hostname' => $data['hostname'], 'project_id' => $project->id],
            [
                'organization_id' => $project->organization_id,
                'name' => $data['hostname'],
                'ip_address' => $data['ip_address'] ?? $request->ip(),
                'os' => $data['os'],
                'os_version' => $data['os_version'] ?? null,
                'arch' => $data['arch'] ?? null,
                'cpu_count' => $data['cpu_count'] ?? null,
                'memory_total' => $data['memory_total'] ?? null,
                'disk_total' => $data['disk_total'] ?? null,
                'status' => 'online',
            ]
        );

        $agent = Agent::firstOrNew(
            ['server_id' => $server->id, 'project_id' => $project->id],
        );

        $agent->fill([
            'agent_id' => $agent->agent_id ?: 'agt_'.Str::random(16),
            'version' => $data['version'],
            'status' => 'active',
            'connected_at' => now(),
            'capabilities' => $data['capabilities'] ?? [],
        ])->save();

        // Issue a fresh token. The plaintext value is shown to the agent
        // here exactly once and never persisted in plaintext.
        $token = $agent->issueToken();

        $server->update(['last_heartbeat_at' => now(), 'status' => 'online']);
        $project->forceFill(['last_event_at' => now()])->save();

        return response()->json([
            'agent_id' => $agent->agent_id,
            'agent_token' => $token,
            'server_id' => $server->id,
            'config' => $this->getAgentConfig($project, $agent),
        ]);
    }

    public function connect(Request $request): JsonResponse
    {
        $agent = $request->attributes->get('agent');
        $agent->update(['status' => 'active', 'connected_at' => now(), 'disconnected_at' => null]);
        $agent->server->update(['status' => 'online', 'last_heartbeat_at' => now()]);

        return response()->json([
            'status' => 'connected',
            'config' => $this->getAgentConfig($agent->project, $agent),
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $agent = $request->attributes->get('agent');
        $agent->update(['status' => 'inactive', 'disconnected_at' => now()]);
        $agent->server->update(['status' => 'offline']);

        return response()->json(['status' => 'disconnected']);
    }

    public function getConfig(Request $request): JsonResponse
    {
        $agent = $request->attributes->get('agent');

        return response()->json($this->getAgentConfig($agent->project, $agent));
    }

    public function getSignatures(Request $request): JsonResponse
    {
        return response()->json([
            'version' => '1.0.0',
            'updated_at' => now(),
            'signatures' => config('guard.signatures', $this->defaultSignatures()),
        ]);
    }

    private function getAgentConfig(Project $project, Agent $agent): array
    {
        $settings = $project->settings ?? [];

        return [
            'scan_interval' => (int) ($settings['scan_interval'] ?? 300),
            'heartbeat_interval' => (int) ($settings['heartbeat_interval'] ?? 60),
            'batch_size' => 50,
            'monitored_paths' => $settings['monitored_paths']
                ?? config('guard.monitored_paths', ['/var/www', '/public', '/storage', '/uploads']),
            'excluded_paths' => $settings['excluded_paths']
                ?? ['/vendor', '/node_modules', '/.git'],
            'dangerous_extensions' => ['php', 'phtml', 'php3', 'php4', 'php5', 'py', 'rb', 'sh', 'bash'],
            'auto_quarantine' => (bool) ($settings['auto_quarantine'] ?? false),
            'realtime_monitoring' => true,
        ];
    }

    private function defaultSignatures(): array
    {
        return [
            ['id' => 'SIG001', 'pattern' => 'eval\s*\(\s*base64_decode', 'type' => 'php_obfuscation',      'severity' => 'high'],
            ['id' => 'SIG002', 'pattern' => 'shell_exec|passthru|system|exec\s*\(', 'type' => 'code_execution', 'severity' => 'high'],
            ['id' => 'SIG003', 'pattern' => 'preg_replace\s*\(.*\/e', 'type' => 'php_rce',                'severity' => 'critical'],
            ['id' => 'SIG004', 'pattern' => 'FilesMan|c99|r57|WSO shell', 'type' => 'web_shell',          'severity' => 'critical'],
            ['id' => 'SIG005', 'pattern' => 'base64_decode.*gzinflate|gzuncompress.*base64', 'type' => 'payload_obfuscation', 'severity' => 'high'],
            ['id' => 'SIG006', 'pattern' => 'strrev\s*\(.*base64_decode', 'type' => 'obfuscation_chain',  'severity' => 'high'],
            ['id' => 'SIG007', 'pattern' => 'import subprocess|subprocess\.Popen|os\.system', 'type' => 'python_execution', 'severity' => 'medium'],
            ['id' => 'SIG008', 'pattern' => 'pickle\.loads|marshal\.loads', 'type' => 'python_deserialization', 'severity' => 'high'],
            ['id' => 'SIG009', 'pattern' => 'xmrig|minerd|cryptonight', 'type' => 'crypto_miner',         'severity' => 'high'],
            ['id' => 'SIG010', 'pattern' => 'bash\s+-i\s+>&\s+/dev/tcp', 'type' => 'reverse_shell',      'severity' => 'critical'],
        ];
    }
}
