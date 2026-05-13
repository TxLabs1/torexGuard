<?php

namespace App\Http\Controllers;

use App\Models\Threat;

class IncidentController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Threat::class);

        $incidents = Threat::with(['server', 'project'])
            ->whereIn('severity', ['critical', 'high'])
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
            ->latest('detected_at')
            ->paginate(20);

        return view('incidents.index', compact('incidents'));
    }

    public function show(Threat $threat)
    {
        $this->authorize('view', $threat);

        $threat->load(['server', 'project', 'logs.user', 'actions', 'quarantineFile']);

        return view('incidents.show', compact('threat'));
    }
}
