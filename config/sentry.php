<?php

use App\Support\GitRevision;

/**
 * Sentry configuration.
 *
 * Release tags are derived from `SENTRY_RELEASE` (set by CI from the git SHA)
 * or, in development, from a `.git/refs/HEAD` lookup so local errors are still
 * attributable to a specific commit.
 */
return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    'release' => env('SENTRY_RELEASE', GitRevision::current()),

    'environment' => env('APP_ENV', 'production'),

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.0) !== null
        ? (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0)
        : null,
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.0) !== null
        ? (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0)
        : null,

    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
    ],

    'tracing' => [
        'queue_jobs' => true,
        'queue_job_transactions' => true,
        'sql_queries' => true,
        'redis_commands' => false,
        'views' => true,
    ],

    'integrations' => [
        // The Laravel default integrations are auto-registered.
    ],
];
