<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'data'         => $notifications->map(fn($n) => [
                'id'        => $n->id,
                'type'      => $n->data['type'],
                'data'      => $n->data,
                'read'      => !is_null($n->read_at),
                'created_at'=> $n->created_at,
            ]),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'next_page'    => $notifications->nextPageUrl(),
        ]);
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    // POST /api/notifications/read-all
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // PATCH /api/notifications/{id}/read
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }
}
