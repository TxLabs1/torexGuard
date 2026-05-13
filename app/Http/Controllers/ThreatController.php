<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\Threat;
use App\Models\ThreatLog;
use App\Services\ThreatResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThreatController extends Controller
{
    public function __construct(private ThreatResponseService $response) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Threat::class);

        // The OrganizationScope global scope handles org isolation.
        $query = Threat::with(['server', 'project']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_path', 'like', "%{$search}%")
                  ->orWhere('threat_id', 'like', "%{$search}%")
                  ->orWhere('source_ip', 'like', "%{$search}%");
            });
        }

        foreach (['severity', 'status', 'type', 'server_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->string($f));
            }
        }

        $threats = $query->latest('detected_at')->paginate(20);
        $servers = Server::query()->get(['id', 'name', 'hostname']);

        $base   = Threat::query();
        $counts = [
            'critical' => (clone $base)->where('severity', 'critical')->count(),
            'high'     => (clone $base)->where('severity', 'high')->count(),
            'medium'   => (clone $base)->where('severity', 'medium')->count(),
            'low'      => (clone $base)->where('severity', 'low')->count(),
        ];

        return view('threats.index', compact('threats', 'servers', 'counts'));
    }

    public function show(Threat $threat)
    {
        $this->authorize('view', $threat);

        $threat->load(['server', 'project', 'logs.user', 'actions', 'quarantineFile']);

        return view('threats.show', compact('threat'));
    }

    public function resolve(Threat $threat)
    {
        $this->authorize('resolve', $threat);

        $threat->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => Auth::id()]);
        ThreatLog::create([
            'threat_id' => $threat->id,
            'user_id'   => Auth::id(),
            'action'    => 'resolved',
            'note'      => 'Manually resolved',
        ]);

        return response()->json(['success' => true, 'message' => 'Threat marked as resolved']);
    }

    public function quarantine(Threat $threat)
    {
        $this->authorize('quarantine', $threat);

        return $this->response->quarantine($threat)
            ? response()->json(['success' => true,  'message' => 'File quarantined successfully'])
            : response()->json(['success' => false, 'message' => 'Quarantine failed'], 500);
    }

    public function markFalsePositive(Threat $threat)
    {
        $this->authorize('markFalsePositive', $threat);

        $threat->update(['status' => 'false_positive']);
        ThreatLog::create([
            'threat_id' => $threat->id,
            'user_id'   => Auth::id(),
            'action'    => 'false_positive',
        ]);

        return response()->json(['success' => true, 'message' => 'Marked as false positive']);
    }

    public function investigate(Threat $threat)
    {
        $this->authorize('investigate', $threat);

        $threat->update(['status' => 'investigating']);
        ThreatLog::create([
            'threat_id' => $threat->id,
            'user_id'   => Auth::id(),
            'action'    => 'investigating',
        ]);

        return response()->json(['success' => true, 'message' => 'Threat under investigation']);
    }
}
