<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Server;
use App\Models\Agent;
use App\Models\Threat;
use App\Models\Alert;
use App\Models\ScanResult;
use App\Models\QuarantineFile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🛡️  Seeding Torex Guard...');

        $this->call(RolesAndPermissionsSeeder::class);

        // ── Admin User ────────────────────────────────────────────
        $user = User::create([
            'name'     => 'Sky Lord',
            'email'    => 'admin@torexlabs.cloud',
            'password' => Hash::make('TorexGuard@2025'),
            'role'     => 'superadmin',
            'is_active'=> true,
        ]);
        $user->assignRole('superadmin');

        $this->command->info('✅ Admin user created: admin@torexlabs.cloud / TorexGuard@2025');

        // ── Organization ──────────────────────────────────────────
        $org = Organization::create([
            'name'         => 'Torex Labs',
            'slug'         => 'torex-labs',
            'email'        => 'security@torexlabs.cloud',
            'website'      => 'https://torexlabs.cloud',
            'plan'         => 'startup',
            'max_servers'  => 10,
            'max_projects' => 50,
            'is_active'    => true,
        ]);

        $org->users()->attach($user->id, ['role' => 'owner']);

        // ── Projects ──────────────────────────────────────────────
        $laravelProject = new Project([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'name'            => 'Production API',
            'slug'            => 'production-api-'.Str::random(6),
            'description'     => 'Main Laravel production backend API',
            'type'            => 'laravel',
            'environment'     => 'production',
            'risk_score'      => 72,
            'status'          => 'active',
            'last_scan_at'    => now()->subHours(2),
        ]);
        $laravelProject->save();
        $laravelProject->issueApiKey();

        $pythonProject = new Project([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'name'            => 'ML Pipeline Service',
            'slug'            => 'ml-pipeline-'.Str::random(6),
            'description'     => 'Python FastAPI ML inference service',
            'type'            => 'python',
            'environment'     => 'production',
            'risk_score'      => 24,
            'status'          => 'active',
        ]);
        $pythonProject->save();
        $pythonProject->issueApiKey();

        // ── Servers ───────────────────────────────────────────────
        $servers = [
            [
                'project_id'       => $laravelProject->id,
                'organization_id'  => $org->id,
                'name'             => 'Web Server 01',
                'hostname'         => 'prod-web-01.torexlabs.cloud',
                'ip_address'       => '10.0.1.10',
                'public_ip'        => '41.58.120.10',
                'os'               => 'Ubuntu',
                'os_version'       => '24.04 LTS',
                'arch'             => 'x86_64',
                'cpu_count'        => 4,
                'memory_total'     => 8589934592,
                'disk_total'       => 107374182400,
                'status'           => 'online',
                'cpu_usage'        => 34.5,
                'memory_usage'     => 61.2,
                'disk_usage'       => 48.7,
                'last_heartbeat_at'=> now()->subSeconds(30),
            ],
            [
                'project_id'       => $laravelProject->id,
                'organization_id'  => $org->id,
                'name'             => 'Database Server',
                'hostname'         => 'prod-db-01.torexlabs.cloud',
                'ip_address'       => '10.0.1.20',
                'public_ip'        => '41.58.120.20',
                'os'               => 'Ubuntu',
                'os_version'       => '22.04 LTS',
                'arch'             => 'x86_64',
                'cpu_count'        => 8,
                'memory_total'     => 17179869184,
                'disk_total'       => 536870912000,
                'status'           => 'online',
                'cpu_usage'        => 18.3,
                'memory_usage'     => 74.8,
                'disk_usage'       => 62.1,
                'last_heartbeat_at'=> now()->subSeconds(45),
            ],
            [
                'project_id'       => $pythonProject->id,
                'organization_id'  => $org->id,
                'name'             => 'ML Worker Node',
                'hostname'         => 'ml-worker-01.torexlabs.cloud',
                'ip_address'       => '10.0.2.10',
                'public_ip'        => '41.58.120.30',
                'os'               => 'Debian',
                'os_version'       => '12',
                'arch'             => 'x86_64',
                'cpu_count'        => 16,
                'memory_total'     => 34359738368,
                'disk_total'       => 214748364800,
                'status'           => 'warning',
                'cpu_usage'        => 87.4,
                'memory_usage'     => 91.2,
                'disk_usage'       => 55.3,
                'last_heartbeat_at'=> now()->subMinutes(3),
            ],
        ];

        $createdServers = [];
        foreach ($servers as $serverData) {
            $server = Server::create($serverData);
            $createdServers[] = $server;

            $agent = new Agent([
                'server_id'         => $server->id,
                'project_id'        => $serverData['project_id'],
                'agent_id'          => 'agt_'.Str::random(16),
                'version'           => '1.0.0',
                'status'            => $server->status === 'online' ? 'active' : 'inactive',
                'connected_at'      => now()->subHours(rand(1, 24)),
                'last_heartbeat_at' => $serverData['last_heartbeat_at'],
                'capabilities'      => ['file_scan', 'process_monitor', 'file_integrity', 'network_monitor'],
            ]);
            $agent->save();
            $agent->issueToken();
        }

        // ── Threats ───────────────────────────────────────────────
        $threatData = [
            [
                'server'     => $createdServers[0],
                'project'    => $laravelProject,
                'type'       => 'web_shell',
                'severity'   => 'critical',
                'title'      => 'PHP Web Shell Detected in Uploads Directory',
                'description'=> 'A PHP web shell was detected at /public/uploads/shell.php. File contains eval(base64_decode()) pattern matching known C99 shell signature.',
                'file_path'  => '/var/www/html/public/uploads/images/shell.php',
                'file_hash'  => hash('sha256', 'webshell_content_' . rand()),
                'file_size'  => 4821,
                'status'     => 'quarantined',
                'confidence' => 98,
                'signatures' => ['eval(base64_decode', 'c99shell', '$_POST eval'],
                'detected_at'=> now()->subHours(3),
            ],
            [
                'server'     => $createdServers[0],
                'project'    => $laravelProject,
                'type'       => 'obfuscated_code',
                'severity'   => 'high',
                'title'      => 'Obfuscated PHP Payload in Storage',
                'description'=> 'Heavily obfuscated PHP file found in /storage directory. Multiple layers of base64 and gzinflate encoding detected.',
                'file_path'  => '/var/www/html/storage/app/public/cache/.htaccess.php',
                'file_hash'  => hash('sha256', 'obfuscated_' . rand()),
                'file_size'  => 12048,
                'status'     => 'active',
                'confidence' => 87,
                'signatures' => ['eval(gzinflate', 'base64_decode+gzinflate chain'],
                'detected_at'=> now()->subHours(1),
            ],
            [
                'server'     => $createdServers[2],
                'project'    => $pythonProject,
                'type'       => 'crypto_miner',
                'severity'   => 'high',
                'title'      => 'Crypto Mining Process Detected',
                'description'=> 'XMRig crypto miner process identified. High CPU usage (87%) correlates with mining activity targeting Monero.',
                'file_path'  => '/tmp/.xmrig',
                'file_hash'  => hash('sha256', 'xmrig_' . rand()),
                'file_size'  => 2097152,
                'process_name'=> 'xmrig',
                'process_id' => 14822,
                'status'     => 'investigating',
                'confidence' => 95,
                'signatures' => ['xmrig', 'stratum+tcp://', 'cryptonight'],
                'detected_at'=> now()->subMinutes(45),
            ],
            [
                'server'     => $createdServers[0],
                'project'    => $laravelProject,
                'type'       => 'brute_force',
                'severity'   => 'medium',
                'title'      => 'SSH Brute Force Attack Detected',
                'description'=> '847 failed SSH login attempts from IP 192.168.1.105 in the last 10 minutes.',
                'source_ip'  => '192.168.1.105',
                'source_country' => 'CN',
                'status'     => 'active',
                'confidence' => 92,
                'signatures' => [],
                'detected_at'=> now()->subMinutes(15),
            ],
            [
                'server'     => $createdServers[1],
                'project'    => $laravelProject,
                'type'       => 'file_modification',
                'severity'   => 'medium',
                'title'      => 'Critical Config File Modified',
                'description'=> '.env file was unexpectedly modified. Database credentials may have been altered.',
                'file_path'  => '/var/www/html/.env',
                'file_hash'  => hash('sha256', 'env_modified_' . rand()),
                'file_size'  => 1204,
                'status'     => 'resolved',
                'confidence' => 80,
                'signatures' => [],
                'detected_at'=> now()->subDays(1),
            ],
        ];

        foreach ($threatData as $td) {
            $threat = Threat::create([
                'threat_id'         => Threat::generateThreatId(),
                'project_id'        => $td['project']->id,
                'server_id'         => $td['server']->id,
                'organization_id'   => $org->id,
                'type'              => $td['type'],
                'severity'          => $td['severity'],
                'title'             => $td['title'],
                'description'       => $td['description'],
                'file_path'         => $td['file_path'] ?? null,
                'file_hash'         => $td['file_hash'] ?? null,
                'file_size'         => $td['file_size'] ?? null,
                'process_name'      => $td['process_name'] ?? null,
                'process_id'        => $td['process_id'] ?? null,
                'source_ip'         => $td['source_ip'] ?? null,
                'source_country'    => $td['source_country'] ?? null,
                'status'            => $td['status'],
                'confidence_score'  => $td['confidence'],
                'signatures_matched'=> $td['signatures'],
                'auto_remediated'   => $td['status'] === 'quarantined',
                'remediation_action'=> $td['status'] === 'quarantined' ? 'quarantine_file' : null,
                'detected_at'       => $td['detected_at'],
                'resolved_at'       => $td['status'] === 'resolved' ? now()->subHours(12) : null,
            ]);

            // Alert for each threat
            Alert::create([
                'project_id'      => $td['project']->id,
                'threat_id'       => $threat->id,
                'organization_id' => $org->id,
                'title'           => '[' . strtoupper($td['severity']) . '] ' . $td['title'],
                'message'         => $td['description'],
                'severity'        => $td['severity'],
                'is_read'         => $td['status'] === 'resolved',
            ]);

            // Quarantine record for quarantined threats
            if ($td['status'] === 'quarantined') {
                QuarantineFile::create([
                    'threat_id'      => $threat->id,
                    'project_id'     => $td['project']->id,
                    'server_id'      => $td['server']->id,
                    'original_path'  => $td['file_path'],
                    'quarantine_path'=> '/var/torex-guard/quarantine/' . $threat->threat_id . '_' . basename($td['file_path']),
                    'file_hash'      => $td['file_hash'],
                    'file_size'      => $td['file_size'],
                    'status'         => 'quarantined',
                    'quarantined_at' => $td['detected_at']->addMinutes(1),
                ]);
            }
        }

        // ── Scan Results ──────────────────────────────────────────
        foreach ($createdServers as $server) {
            for ($i = 0; $i < 3; $i++) {
                $start = now()->subDays($i)->subHours(rand(1,6));
                ScanResult::create([
                    'project_id'     => $server->project_id,
                    'server_id'      => $server->id,
                    'scan_id'        => 'SCN-' . strtoupper(Str::random(10)),
                    'type'           => $i === 0 ? 'full' : 'quick',
                    'status'         => 'completed',
                    'files_scanned'  => rand(1200, 8400),
                    'threats_found'  => rand(0, 3),
                    'files_clean'    => rand(1100, 8300),
                    'duration_seconds'=> rand(45, 320),
                    'started_at'     => $start,
                    'completed_at'   => $start->addSeconds(rand(45, 320)),
                ]);
            }
        }

        $this->command->info('✅ Seeding complete!');
        $this->command->line('');
        $this->command->line('  Login: admin@torexlabs.cloud');
        $this->command->line('  Pass:  TorexGuard@2025');
        $this->command->line('');
    }
}
