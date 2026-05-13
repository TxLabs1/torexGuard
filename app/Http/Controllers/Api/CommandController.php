<?php

namespace App\Http\Controllers\Api;

use App\Models\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Agent command channel.
 *
 * The dashboard creates Action rows when an operator clicks "Quarantine",
 * "Kill process", etc. The agent long-polls /commands/pending, executes,
 * then PATCHes /commands/{id} with the result.
 *
 * Note: full implementation lives in Phase 2 (p2-go-command-channel) where
 * the actions table is extended with server_id + dispatched_at so commands
 * can target a specific server rather than going via the threat relation.
 */
class CommandController
{
    public function pending(Request $request): JsonResponse
    {
        $agent = $request->attributes->get('agent');

        $commands = Action::query()
            ->where('status', 'pending')
            ->where(function ($q) use ($agent) {
                $q->where('server_id', $agent->server_id)
                    ->orWhereHas('threat', fn ($q) => $q->where('server_id', $agent->server_id));
            })
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'type', 'target', 'threat_id', 'is_automated']);

        Action::query()
            ->whereIn('id', $commands->pluck('id'))
            ->update(['status' => 'executing']);

        return response()->json([
            'commands' => $commands->map(fn ($c) => [
                'id' => $c->id,
                'type' => $c->type,
                'target' => $c->target,
                'threat_id' => $c->threat_id,
                'automated' => (bool) $c->is_automated,
            ]),
        ]);
    }

    public function update(Request $request, Action $command): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:success,failed,rolled_back',
            'result' => 'nullable|string|max:65535',
            'error' => 'nullable|string|max:2048',
        ]);

        $command->update([
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'error' => $data['error'] ?? null,
            'executed_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
