<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', ['notifications' => $notifications]);
    }

    /** Lightweight JSON summary polled by the app shell for live badge/toast updates. */
    public function summary(Request $request)
    {
        $unread = $request->user()->notifications()->whereNull('read_at');

        return response()->json([
            'unread' => (clone $unread)->count(),
            'items' => $unread->latest()->limit(5)->get(['id', 'title', 'message', 'url', 'created_at'])
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => Str::limit($n->message, 120),
                    'url' => $n->url,
                    'time' => $n->created_at->diffForHumans(),
                ]),
            'now' => now()->toIso8601String(),
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return redirect($notification->url ?: route('notifications.index'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
