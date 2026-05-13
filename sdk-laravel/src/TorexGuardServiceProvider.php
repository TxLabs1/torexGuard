<?php
// sdk-laravel/src/TorexGuardServiceProvider.php

namespace TorexLabs\Guard;

use Illuminate\Support\ServiceProvider;
use TorexLabs\Guard\Commands\InstallCommand;
use TorexLabs\Guard\Commands\ScanCommand;
use TorexLabs\Guard\Commands\ConnectCommand;
use TorexLabs\Guard\Commands\StatusCommand;

class TorexGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/torex-guard.php', 'torex-guard');

        $this->app->singleton(GuardClient::class, function ($app) {
            return new GuardClient(config('torex-guard'));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ScanCommand::class,
                ConnectCommand::class,
                StatusCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/torex-guard.php' => config_path('torex-guard.php'),
            ], 'torex-guard-config');
        }

        // Auto-register middleware if enabled
        if (config('torex-guard.monitor_requests', true)) {
            $this->app['router']->pushMiddlewareToGroup('web', \TorexLabs\Guard\Middleware\MonitorRequests::class);
        }
    }
}
