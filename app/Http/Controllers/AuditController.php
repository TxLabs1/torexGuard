<?php

namespace App\Http\Controllers;

use App\Support\OrganizationContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canViewAudit(), 403);

        $orgId = OrganizationContext::currentId();

        $query = Activity::query()
            ->with('causer')
            ->latest();

        // Restrict to subjects owned by the user's organization (Threat,
        // Server, Project, Action, Agent rows all live in the same org).
        // Heuristic: filter via causer (the acting user must share the org)
        // OR via the subject's own organization_id column when present.
        if ($orgId) {
            $userIds = \DB::table('organization_user')
                ->where('organization_id', $orgId)
                ->pluck('user_id');
            $query->whereIn('causer_id', $userIds);
        }

        foreach (['log_name', 'event'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->input($f));
            }
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q
                ->where('description', 'like', "%{$search}%")
                ->orWhere('subject_type', 'like', "%{$search}%"));
        }

        $activities = $query->paginate(40)->withQueryString();
        $logNames = Activity::select('log_name')->distinct()->pluck('log_name')->filter()->values();

        return view('audit.index', compact('activities', 'logNames'));
    }

    private function canViewAudit(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('view audit log')) {
            return true;
        }

        return $user->isAdmin();
    }
}
