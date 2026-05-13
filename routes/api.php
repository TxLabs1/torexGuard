<?php

// routes/api.php

use App\Http\Controllers\Api\AgentAuthController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FileIntegrityController;
use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\ThreatIngestionController;
use Illuminate\Support\Facades\Route;

// ── Agent endpoints ───────────────────────────────────────────
Route::prefix('v1/agent')->middleware('as.system')->group(function () {

    // Public: bootstrap — agent has only a project_api_key at this point.
    Route::middleware('throttle:agent-register')
        ->post('/register', [AgentAuthController::class, 'register']);

    // Authenticated: agent has been issued a token.
    Route::middleware(['agent.auth', 'throttle:agent'])->group(function () {

        Route::post('/connect', [AgentAuthController::class, 'connect']);
        Route::post('/disconnect', [AgentAuthController::class, 'disconnect']);

        Route::post('/heartbeat', [HeartbeatController::class, 'receive']);

        Route::post('/events', [EventController::class, 'ingest']);
        Route::post('/events/batch', [EventController::class, 'ingestBatch']);

        Route::post('/threats', [ThreatIngestionController::class, 'report']);
        Route::post('/threats/batch', [ThreatIngestionController::class, 'reportBatch']);

        Route::post('/scans/start', [ScanController::class, 'start']);
        Route::post('/scans/{scanId}/update', [ScanController::class, 'update']);
        Route::post('/scans/{scanId}/complete', [ScanController::class, 'complete']);

        Route::post('/integrity/snapshot', [FileIntegrityController::class, 'submitSnapshot']);
        Route::post('/integrity/changes', [FileIntegrityController::class, 'reportChanges']);

        // Command channel: dashboard creates Actions, agent long-polls & acks here.
        Route::get('/commands/pending', [CommandController::class, 'pending']);
        Route::patch('/commands/{command}', [CommandController::class, 'update']);

        Route::get('/config', [AgentAuthController::class, 'getConfig']);
        Route::get('/signatures', [AgentAuthController::class, 'getSignatures']);
    });
});

// ── Dashboard API (authenticated via Sanctum) ────────────────
Route::prefix('v1/dashboard')->middleware(['auth:sanctum'])->group(function () {

    Route::get('/stats', [DashboardApiController::class, 'stats']);
    Route::get('/threats/recent', [DashboardApiController::class, 'recentThreats']);
    Route::get('/threats/timeline', [DashboardApiController::class, 'threatTimeline']);
    Route::get('/servers/status', [DashboardApiController::class, 'serverStatus']);
    Route::get('/alerts/unread', [DashboardApiController::class, 'unreadAlerts']);
    Route::get('/events/stream', [DashboardApiController::class, 'eventStream']);
});

// ── Public endpoints ──────────────────────────────────────────
Route::prefix('v1/public')->group(function () {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'version' => config('guard.version'),
    ]));
});
