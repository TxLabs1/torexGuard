<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\ScanResult;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanController extends Controller
{
    public function index()
    {
        $scans = ScanResult::with(['project', 'server'])
            ->whereHas('project') // org-scoped via Project's global scope
            ->latest('started_at')->paginate(15);

        return view('scans.index', compact('scans'));
    }

    public function start(Request $request)
    {
        $data = $request->validate(['server_id' => ['required', 'integer', 'exists:servers,id']]);

        $server = Server::query()->where('id', $data['server_id'])->firstOrFail();
        $this->authorize('view', $server);

        // Queue a command for the agent. It long-polls /api/v1/agent/commands/pending.
        Action::create([
            'server_id'    => $server->id,
            'project_id'   => $server->project_id,
            'triggered_by' => Auth::id(),
            'type'         => 'send_alert', // re-using existing enum; full force_scan added in p3 enum extension
            'status'       => 'pending',
            'is_automated' => false,
            'target'       => 'force_scan',
            'dispatched_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Scan initiated. The agent will pick it up on its next poll.');
    }

    public function show(ScanResult $scan)
    {
        $owned = ScanResult::whereHas('project')->where('id', $scan->id)->exists();
        abort_unless($owned, 403);

        return view('scans.show', compact('scan'));
    }
}
