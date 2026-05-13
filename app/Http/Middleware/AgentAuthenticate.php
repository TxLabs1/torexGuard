<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Agent-Token')
            ?? $request->query('agent_token');

        if (! is_string($token) || $token === '') {
            return response()->json(['error' => 'Agent token required'], 401);
        }

        $agent = Agent::with(['server', 'project'])
            ->where('token_hash', hash('sha256', $token))
            ->where('status', '!=', 'error')
            ->first();

        if (! $agent) {
            return response()->json(['error' => 'Invalid or expired agent token'], 401);
        }

        if (! $agent->project || $agent->project->status !== 'active') {
            return response()->json(['error' => 'Project inactive or suspended'], 403);
        }

        // Touch the heartbeat column on a best-effort basis so the dashboard
        // shows "last seen" without requiring a heartbeat round-trip.
        $agent->forceFill(['last_heartbeat_at' => now()])->saveQuietly();

        $request->attributes->set('agent', $agent);
        $request->attributes->set('agent_server', $agent->server);
        $request->attributes->set('agent_project', $agent->project);

        return $next($request);
    }
}
