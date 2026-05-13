<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ThreatController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\QuarantineController;

// ── Public agent installer endpoints ─────────────────────────
// These serve the install scripts from disk so customers can
// `curl -fsSL <host>/install.sh | bash -s -- --api-key=...`.
Route::get('/install.sh', function () {
    return response()->file(
        base_path('agent/install/install.sh'),
        ['Content-Type' => 'text/x-shellscript; charset=utf-8'],
    );
})->name('install.sh');

Route::get('/install.ps1', function () {
    return response()->file(
        base_path('agent/install/install.ps1'),
        ['Content-Type' => 'application/x-powershell; charset=utf-8'],
    );
})->name('install.ps1');

// ── Auth Routes ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.post');
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:login')
        ->name('register.post');
    Route::get('/forgot-password', [LoginController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [LoginController::class, 'sendResetLink'])
        ->middleware('throttle:login')
        ->name('password.email');
});

// ── 2FA: challenge during login (no auth middleware yet) ─────
Route::middleware('guest')->group(function () {
    Route::get('/login/2fa',  [TwoFactorController::class, 'challenge'])
        ->name('login.2fa.challenge');
    Route::post('/login/2fa', [TwoFactorController::class, 'verifyChallenge'])
        ->middleware('throttle:login')
        ->name('login.2fa.verify');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Protected Routes ─────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
        Route::post('/{project}/rotate-key', [ProjectController::class, 'rotateKey'])->name('rotate-key');
    });

    // Servers
    Route::prefix('servers')->name('servers.')->group(function () {
        Route::get('/', [ServerController::class, 'index'])->name('index');
        Route::get('/create', [ServerController::class, 'create'])->name('create');
        Route::post('/', [ServerController::class, 'store'])->name('store');
        Route::get('/{server}', [ServerController::class, 'show'])->name('show');
        Route::delete('/{server}', [ServerController::class, 'destroy'])->name('destroy');
        Route::get('/{server}/metrics', [ServerController::class, 'metrics'])->name('metrics');
    });

    // Threats
    Route::prefix('threats')->name('threats.')->group(function () {
        Route::get('/', [ThreatController::class, 'index'])->name('index');
        Route::get('/{threat}', [ThreatController::class, 'show'])->name('show');
        Route::post('/{threat}/resolve', [ThreatController::class, 'resolve'])->name('resolve');
        Route::post('/{threat}/quarantine', [ThreatController::class, 'quarantine'])->name('quarantine');
        Route::post('/{threat}/false-positive', [ThreatController::class, 'markFalsePositive'])->name('false-positive');
        Route::post('/{threat}/investigate', [ThreatController::class, 'investigate'])->name('investigate');
    });

    // Alerts
    Route::prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::post('/{alert}/read', [AlertController::class, 'markRead'])->name('read');
        Route::post('/read-all', [AlertController::class, 'markAllRead'])->name('read-all');
    });

    // Incidents
    Route::prefix('incidents')->name('incidents.')->group(function () {
        Route::get('/', [IncidentController::class, 'index'])->name('index');
        Route::get('/{threat}', [IncidentController::class, 'show'])->name('show');
    });

    // Scans
    Route::prefix('scans')->name('scans.')->group(function () {
        Route::get('/', [ScanController::class, 'index'])->name('index');
        Route::post('/start', [ScanController::class, 'start'])->name('start');
        Route::get('/{scan}', [ScanController::class, 'show'])->name('show');
    });

    // Quarantine
    Route::prefix('quarantine')->name('quarantine.')->group(function () {
        Route::get('/', [QuarantineController::class, 'index'])->name('index');
        Route::post('/{file}/restore', [QuarantineController::class, 'restore'])->name('restore');
        Route::delete('/{file}', [QuarantineController::class, 'destroy'])->name('destroy');
    });

    // Audit log
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Notification inbox
    Route::get('/notifications',                 [\App\Http\Controllers\NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread.json',     [\App\Http\Controllers\NotificationCenterController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/{id}/read',      [\App\Http\Controllers\NotificationCenterController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',       [\App\Http\Controllers\NotificationCenterController::class, 'markAllRead'])->name('notifications.read-all');

    // 2FA management (enrollment / disable for authenticated users)
    Route::prefix('settings/2fa')->name('2fa.')->group(function () {
        Route::get('/',          [TwoFactorController::class, 'show'])->name('show');
        Route::post('/confirm',  [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::post('/disable',  [TwoFactorController::class, 'disable'])->name('disable');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/organization', [SettingsController::class, 'organization'])->name('organization');
        Route::put('/organization', [SettingsController::class, 'updateOrganization'])->name('organization.update');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::get('/api-tokens', [SettingsController::class, 'apiTokens'])->name('api-tokens');
        Route::post('/api-tokens', [SettingsController::class, 'createToken'])->name('api-tokens.create');
        Route::delete('/api-tokens/{token}', [SettingsController::class, 'revokeToken'])->name('api-tokens.revoke');
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
    });
});
