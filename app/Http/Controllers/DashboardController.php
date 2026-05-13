<?php

namespace App\Http\Controllers;

use App\Models\ScanResult;
use App\Models\Server;
use App\Models\Threat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $org = Auth::user()->organizations()->first();

        $stats = $this->buildStats();
        $recentThreats = Threat::with(['server'])->latest('detected_at')->take(15)->get();
        $servers = Server::query()->latest('last_heartbeat_at')->take(6)->get();
        $riskScore = $org?->overallRiskScore() ?? 0;
        $chartData = $this->buildChartData();
        $threatTypes = $this->buildThreatTypes();

        return view('dashboard.index', compact(
            'stats', 'recentThreats', 'servers',
            'riskScore', 'chartData', 'threatTypes'
        ));
    }

    public function stats()
    {
        return response()->json($this->buildStats());
    }

    private function buildStats(): array
    {
        $base = Threat::query();

        return [
            'critical_threats' => (clone $base)->where('severity', 'critical')->where('status', 'active')->count(),
            'active_incidents' => (clone $base)->whereIn('status', ['active', 'investigating'])->count(),
            'investigating' => (clone $base)->where('status', 'investigating')->count(),
            'servers_online' => Server::query()->where('status', 'online')->count(),
            'servers_total' => Server::query()->count(),
            'quarantined' => (clone $base)->where('status', 'quarantined')->count(),
            'scans_today' => ScanResult::query()
                ->whereHas('project')
                ->whereDate('started_at', today())->count(),
            'new_today' => (clone $base)->whereDate('detected_at', today())->count(),
            'total_threats' => (clone $base)->count(),
            'sev_critical' => (clone $base)->where('severity', 'critical')->count(),
            'sev_high' => (clone $base)->where('severity', 'high')->count(),
            'sev_medium' => (clone $base)->where('severity', 'medium')->count(),
            'sev_low' => (clone $base)->where('severity', 'low')->count(),
        ];
    }

    private function buildChartData(): array
    {
        $labels = [];
        $critical = [];
        $high = [];
        $medium = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');

            $base = Threat::query()->whereDate('detected_at', $date);

            $critical[] = (clone $base)->where('severity', 'critical')->count();
            $high[] = (clone $base)->where('severity', 'high')->count();
            $medium[] = (clone $base)->where('severity', 'medium')->count();
        }

        return compact('labels', 'critical', 'high', 'medium');
    }

    private function buildThreatTypes(): array
    {
        return Threat::query()
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->mapWithKeys(fn ($count, $type) => [
                ucwords(str_replace('_', ' ', $type)) => $count,
            ])
            ->toArray();
    }
}
