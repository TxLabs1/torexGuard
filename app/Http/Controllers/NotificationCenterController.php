<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationCenterController extends Controller
{
    public function index(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(25);

        return view('notifications.index', compact('notifications'));
    }

    public function unread(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'recent' => $user->unreadNotifications()
                ->limit(10)
                ->get()
                ->map(fn ($n) => [
                    'id'         => $n->id,
                    'type'       => $n->data['type']  ?? 'notification',
                    'title'      => $n->data['title'] ?? ($n->data['hostname'] ?? null) ?? 'Notification',
                    'url'        => $n->data['url']   ?? '#',
                    'severity'   => $n->data['severity'] ?? null,
                    'created_at' => $n->created_at?->diffForHumans(),
                ]),
        ]);
    }

    public function markRead(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();
        $notification?->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back();
    }
}
