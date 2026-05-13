<?php

// sdk-laravel/config/torex-guard.php

return [
    'api_key' => env('TOREX_GUARD_API_KEY', ''),
    'host' => env('TOREX_GUARD_HOST', 'https://guard.torexlabs.cloud'),
    'monitor_requests' => env('TOREX_GUARD_MONITOR_REQUESTS', true),
    'auto_scan' => env('TOREX_GUARD_AUTO_SCAN', false),
    'scan_on_deploy' => env('TOREX_GUARD_SCAN_ON_DEPLOY', true),
    'monitored_paths' => [base_path('public'), base_path('storage')],
    'excluded_paths' => [base_path('vendor'), base_path('node_modules')],
];
