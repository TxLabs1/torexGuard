<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ScanResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        $org = Auth::user()->organizations()->first();
        $projects = Project::with(['servers'])
            ->where('organization_id', $org?->id)
            ->latest()->paginate(12);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:laravel,python,node,other',
            'environment' => 'required|in:production,staging,development',
        ]);

        $org = Auth::user()->organizations()->first();
        abort_if(! $org, 422, 'No organization for current user.');

        $project = new Project([
            ...$validated,
            'organization_id' => $org->id,
            'user_id' => Auth::id(),
            'slug' => Str::slug($validated['name']).'-'.Str::random(6),
            'status' => 'active',
        ]);
        $project->save();

        $plaintext = $project->issueApiKey();

        // Flash the plaintext key/secret so the next view can show it ONCE,
        // then they're gone forever.
        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created. Copy the API key below now — it will not be shown again.')
            ->with('plaintext_api_key', $plaintext['api_key'])
            ->with('plaintext_api_secret', $plaintext['api_secret']);
    }

    public function show(Project $project)
    {
        $this->authorizeOrgAccess($project->organization_id);

        $project->load(['servers.agent', 'threats' => fn ($q) => $q->latest()->take(10)]);
        $recentScans = ScanResult::where('project_id', $project->id)->latest()->take(5)->get();

        return view('projects.show', compact('project', 'recentScans'));
    }

    public function edit(Project $project)
    {
        $this->authorizeOrgAccess($project->organization_id);

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeOrgAccess($project->organization_id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'environment' => 'required|in:production,staging,development',
        ]);
        $project->update($validated);

        return redirect()->route('projects.show', $project)->with('success', 'Project updated');
    }

    public function destroy(Project $project)
    {
        $this->authorizeOrgAccess($project->organization_id);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted');
    }

    public function rotateKey(Project $project)
    {
        $this->authorizeOrgAccess($project->organization_id);

        $plaintext = $project->issueApiKey();

        return response()->json([
            'success' => true,
            'api_key' => $plaintext['api_key'],
            'api_secret' => $plaintext['api_secret'],
            'note' => 'Copy this value now. It will not be shown again.',
        ]);
    }

    private function authorizeOrgAccess(int $orgId): void
    {
        $userOrgIds = Auth::user()->organizations()->pluck('organizations.id');
        abort_unless($userOrgIds->contains($orgId), 403);
    }
}
