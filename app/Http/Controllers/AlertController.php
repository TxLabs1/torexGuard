<?php

namespace App\Http\Controllers;

use App\Models\Alert;

class AlertController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Alert::class);

        $alerts = Alert::with(['project', 'threat'])
            ->latest()->paginate(25);

        $unreadCount = Alert::query()->where('is_read', false)->count();

        return view('alerts.index', compact('alerts', 'unreadCount'));
    }

    public function markRead(Alert $alert)
    {
        $this->authorize('update', $alert);

        $alert->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $this->authorize('viewAny', Alert::class);

        Alert::query()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return redirect()->back()->with('success', 'All alerts marked as read');
    }
}
