<?php

// sdk-laravel/src/GuardClient.php

namespace TorexLabs\Guard;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuardClient
{
    private string $apiKey;

    private string $host;

    private string $agentToken = '';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->host = rtrim($config['host'] ?? 'https://guard.torexlabs.cloud', '/');
    }

    public function connect(): array
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->host.'/api/v1/agent/register', [
                'project_api_key' => $this->apiKey,
                'hostname' => gethostname(),
                'os' => PHP_OS,
                'version' => '1.0.0',
                'capabilities' => ['file_scan', 'laravel_sdk'],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->agentToken = $data['agent_token'] ?? '';

            return $data;
        }

        throw new \RuntimeException('Torex Guard connection failed: '.$response->body());
    }

    public function reportThreat(array $threat): void
    {
        try {
            Http::withHeaders($this->headers())
                ->post($this->host.'/api/v1/agent/threats', $threat);
        } catch (\Exception $e) {
            Log::warning('[TorexGuard] Failed to report threat: '.$e->getMessage());
        }
    }

    public function reportEvent(array $event): void
    {
        try {
            Http::withHeaders($this->headers())
                ->post($this->host.'/api/v1/agent/events', $event);
        } catch (\Exception $e) {
            Log::warning('[TorexGuard] Failed to report event: '.$e->getMessage());
        }
    }

    public function heartbeat(array $metrics = []): void
    {
        try {
            Http::withHeaders($this->headers())
                ->post($this->host.'/api/v1/agent/heartbeat', array_merge([
                    'timestamp' => now()->toIso8601String(),
                ], $metrics));
        } catch (\Exception $e) {
        }
    }

    public function scan(?string $path = null): array
    {
        $scanner = new Scanner(config('torex-guard.signatures', []));
        $result = $scanner->scan($path ?? base_path());

        foreach ($result['threats'] as $threat) {
            $this->reportThreat($threat);
        }

        return $result;
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->agentToken,
            'X-Agent-Token' => $this->agentToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'TorexGuardSDK-Laravel/1.0.0',
        ];
    }
}

// ──────────────────────────────────────────────────────────────
// File Scanner
// ──────────────────────────────────────────────────────────────
class Scanner
{
    private array $signatures;

    private array $phpPatterns = [
        '/eval\s*\(\s*base64_decode/i',
        '/eval\s*\(\s*gzinflate/i',
        '/shell_exec\s*\(/i',
        '/passthru\s*\(/i',
        '/preg_replace\s*\(.*\/e/i',
        '/FilesMan|c99shell|r57shell/i',
        '/\$_POST\[.*\].*eval/i',
    ];

    public function __construct(array $signatures = [])
    {
        $this->signatures = $signatures;
    }

    public function scan(string $path): array
    {
        $threats = [];
        $filesScanned = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            if ($this->isExcluded($file->getPathname())) {
                continue;
            }

            $filesScanned++;
            $threat = $this->scanFile($file);
            if ($threat) {
                $threats[] = $threat;
            }
        }

        return ['files_scanned' => $filesScanned, 'threats' => $threats];
    }

    private function scanFile(\SplFileInfo $file): ?array
    {
        $ext = strtolower($file->getExtension());
        if (! in_array($ext, ['php', 'phtml', 'py', 'sh'])) {
            return null;
        }

        $content = file_get_contents($file->getPathname());
        if (! $content) {
            return null;
        }

        $matched = [];
        foreach ($this->phpPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matched[] = $pattern;
            }
        }

        if (empty($matched)) {
            return null;
        }

        return [
            'type' => 'web_shell',
            'severity' => count($matched) >= 3 ? 'critical' : 'high',
            'title' => 'Malicious '.strtoupper($ext).' file: '.$file->getFilename(),
            'file_path' => $file->getPathname(),
            'file_hash' => hash_file('sha256', $file->getPathname()),
            'file_size' => $file->getSize(),
            'confidence_score' => min(100, count($matched) * 25),
            'signatures_matched' => $matched,
            'detected_at' => now()->toIso8601String(),
        ];
    }

    private function isExcluded(string $path): bool
    {
        $excluded = ['/vendor/', '/node_modules/', '/.git/', '/storage/framework/'];
        foreach ($excluded as $ex) {
            if (str_contains($path, $ex)) {
                return true;
            }
        }

        return false;
    }
}

// ──────────────────────────────────────────────────────────────
// Commands
// ──────────────────────────────────────────────────────────────

namespace TorexLabs\Guard\Commands;

use Illuminate\Console\Command;
use TorexLabs\Guard\GuardClient;

class InstallCommand extends Command
{
    protected $signature = 'guard:install';

    protected $description = 'Install and configure Torex Guard';

    public function handle(): void
    {
        $this->info('🛡️  Torex Guard Installation');
        $this->line('');

        if (! config('torex-guard.api_key')) {
            $this->error('API key not set. Add TOREX_GUARD_API_KEY to your .env file.');

            return;
        }

        try {
            $client = app(GuardClient::class);
            $result = $client->connect();
            $this->info('✅ Connected to Torex Guard!');
            $this->line('   Agent ID: '.($result['agent_id'] ?? 'N/A'));
            $this->line('   Server registered and monitoring active.');
        } catch (\Exception $e) {
            $this->error('Connection failed: '.$e->getMessage());
        }
    }
}

class ScanCommand extends Command
{
    protected $signature = 'guard:scan {--path= : Path to scan}';

    protected $description = 'Run a security scan on your application';

    public function handle(): void
    {
        $path = $this->option('path') ?? base_path();
        $this->info('🔍 Scanning: '.$path);

        $client = app(GuardClient::class);
        $result = $client->scan($path);

        $this->line('');
        $this->info('Scan Complete:');
        $this->line('  Files scanned: '.$result['files_scanned']);
        $this->line('  Threats found: '.count($result['threats']));

        if (count($result['threats']) > 0) {
            $this->error('⚠️  Threats detected! Check your Torex Guard dashboard.');
            foreach ($result['threats'] as $threat) {
                $this->line('  ['.strtoupper($threat['severity']).'] '.$threat['file_path']);
            }
        } else {
            $this->info('✅ No threats found. Your application is clean.');
        }
    }
}

class ConnectCommand extends Command
{
    protected $signature = 'guard:connect';

    protected $description = 'Connect this server to Torex Guard';

    public function handle(): void
    {
        $this->info('Connecting to Torex Guard...');
        try {
            $client = app(GuardClient::class);
            $result = $client->connect();
            $this->info('✅ Connected! Agent: '.($result['agent_id'] ?? 'N/A'));
        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());
        }
    }
}

class StatusCommand extends Command
{
    protected $signature = 'guard:status';

    protected $description = 'Show Torex Guard agent status';

    public function handle(): void
    {
        $this->info('Torex Guard Status');
        $this->line('  API Key:    '.(config('torex-guard.api_key') ? '✅ Set' : '❌ Not set'));
        $this->line('  Host:       '.config('torex-guard.host', 'Not configured'));
        $this->line('  Monitoring: '.(config('torex-guard.monitor_requests') ? '✅ Active' : '⏸ Disabled'));
    }
}

// ──────────────────────────────────────────────────────────────
// Request Monitor Middleware
// ──────────────────────────────────────────────────────────────

namespace TorexLabs\Guard\Middleware;

use Closure;
use Illuminate\Http\Request;
use TorexLabs\Guard\GuardClient;

class MonitorRequests
{
    public function __construct(private GuardClient $client) {}

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Detect SQL injection attempts
        $input = $request->all();
        foreach ($input as $key => $value) {
            if (is_string($value) && $this->isSqlInjection($value)) {
                $this->client->reportEvent([
                    'type' => 'sql_injection_attempt',
                    'severity' => 'high',
                    'payload' => ['input_key' => $key, 'source_ip' => $request->ip(), 'url' => $request->fullUrl()],
                    'occurred_at' => now()->toIso8601String(),
                ]);
            }
        }

        return $response;
    }

    private function isSqlInjection(string $value): bool
    {
        $patterns = ['/UNION\s+SELECT/i', '/DROP\s+TABLE/i', '/\'\s+OR\s+\'/i', '/1=1/i', '/;\s*--/'];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
