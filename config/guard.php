<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Torex Guard Version
    |--------------------------------------------------------------------------
    */
    'version' => env('TOREX_GUARD_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Agent Secret
    | Used to sign agent registration requests
    |--------------------------------------------------------------------------
    */
    'agent_secret' => env('TOREX_GUARD_AGENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Quarantine Path
    | Where infected files are moved on the server
    |--------------------------------------------------------------------------
    */
    'quarantine_path' => env('TOREX_GUARD_QUARANTINE_PATH', '/var/torex-guard/quarantine'),

    /*
    |--------------------------------------------------------------------------
    | Event Pipeline
    |--------------------------------------------------------------------------
    */
    'max_events_per_minute' => env('TOREX_GUARD_MAX_EVENTS_PER_MINUTE', 10000),
    'scan_concurrency' => env('TOREX_GUARD_SCAN_CONCURRENCY', 5),
    'threat_retention_days' => env('TOREX_GUARD_THREAT_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Auto Remediation Defaults
    |--------------------------------------------------------------------------
    */
    'auto_quarantine_critical' => env('TOREX_AUTO_QUARANTINE_CRITICAL', false),
    'auto_block_ip' => env('TOREX_AUTO_BLOCK_IP', false),

    /*
    |--------------------------------------------------------------------------
    | Malware Signatures
    | Base signatures loaded by default. Agents fetch full list via API.
    |--------------------------------------------------------------------------
    */
    'signatures' => [
        'php' => [
            'eval\s*\(\s*base64_decode',
            'eval\s*\(\s*gzinflate',
            'shell_exec\s*\(',
            'passthru\s*\(',
            'preg_replace\s*\(.*\/e',
            'FilesMan|c99shell|r57shell|WSO\s+Shell',
            '\$_POST\[.*\].*eval',
            'assert\s*\(\s*\$_(POST|GET|REQUEST)',
        ],
        'python' => [
            'subprocess\.Popen',
            'os\.system\s*\(',
            'pickle\.loads\s*\(',
            'marshal\.loads\s*\(',
            '__import__\s*\(\s*[\'"]os',
        ],
        'crypto_miner' => [
            'xmrig|monero|cryptonight|coinhive',
            'stratum\+tcp://',
            'minerd|cpuminer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitored Paths (defaults sent to agents)
    |--------------------------------------------------------------------------
    */
    'monitored_paths' => [
        '/var/www',
        '/public',
        '/storage',
        '/uploads',
    ],

    'excluded_paths' => [
        '/vendor',
        '/node_modules',
        '/.git',
        '/storage/framework',
        '/storage/logs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Severity Score Weights (for risk calculation)
    |--------------------------------------------------------------------------
    */
    'risk_weights' => [
        'critical' => 15,
        'high' => 5,
        'medium' => 2,
        'low' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plans & Limits
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'free' => [
            'max_servers' => 1,
            'max_projects' => 3,
            'scan_history' => 7,
        ],
        'developer' => [
            'max_servers' => 3,
            'max_projects' => 10,
            'scan_history' => 30,
        ],
        'startup' => [
            'max_servers' => 10,
            'max_projects' => 50,
            'scan_history' => 90,
        ],
        'enterprise' => [
            'max_servers' => -1, // unlimited
            'max_projects' => -1,
            'scan_history' => 365,
        ],
    ],

];
