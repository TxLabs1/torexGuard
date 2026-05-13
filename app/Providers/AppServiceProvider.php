<?php

namespace App\Providers;

use App\Services\GeoIpService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeoIpService::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiters();
    }

    protected function configureRateLimiters(): void
    {
        // Default api group limiter (login routes, dashboard API)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Agent bootstrap: a single IP can register at most 5 new agents/min.
        RateLimiter::for('agent-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Agent runtime: per-token 1000/min covers heartbeats + event batches.
        RateLimiter::for('agent', function (Request $request) {
            $agent = $request->attributes->get('agent');
            $key = is_object($agent) && property_exists($agent, 'id')
                ? $agent->id
                : $request->ip();

            return Limit::perMinute(1000)->by("agent:{$key}");
        });

        // Login: aggressive per-email + per-ip limit to defeat credential stuffing.
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by(mb_strtolower($email).'|'.$request->ip()),
            ];
        });
    }
}
