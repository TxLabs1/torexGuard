<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScanResult;
use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Server::class);

        $servers = Server::with(['project', 'agent', 'threats'])
            ->latest()->paginate(10);

        return view('servers.index', compact('servers'));
    }

    public function create()
    {
        $this->authorize('create', Server::class);

        $projects = Project::query()->get(['id', 'name']);

        return view('servers.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Server::class);

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:100'],
            'hostname' => ['required', 'string', 'max:255'],
        ]);

        // organization_id derived from the project (which is org-scoped).
        $project = Project::query()->where('id', $validated['project_id'])->firstOrFail();

        Server::create([
            ...$validated,
            'organization_id' => $project->organization_id,
        ]);

        return redirect()->route('servers.index')->with('success', 'Server registered');
    }

    public function show(Server $server)
    {
        $this->authorize('view', $server);

        $server->load(['agent', 'project', 'threats' => fn ($q) => $q->latest()->take(20)]);
        $recentScans = ScanResult::query()
            ->where('server_id', $server->id)
            ->latest()->take(5)->get();

        return view('servers.show', compact('server', 'recentScans'));
    }

    public function destroy(Server $server)
    {
        $this->authorize('delete', $server);

        $server->delete();

        return redirect()->route('servers.index')->with('success', 'Server removed');
    }

    public function metrics(Server $server)
    {
        $this->authorize('view', $server);

        return response()->json([
            'cpu_usage' => $server->cpu_usage,
            'memory_usage' => $server->memory_usage,
            'disk_usage' => $server->disk_usage,
            'status' => $server->status,
            'online' => $server->isOnline(),
        ]);
    }
}
