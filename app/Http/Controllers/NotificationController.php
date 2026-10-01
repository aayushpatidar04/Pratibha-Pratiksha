<?php

namespace App\Http\Controllers;

use App\Models\NotificationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get latest notifications for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = NotificationRecipient::query()
            ->with('notification')
            ->where('user_id', $user->id)
            ->whereNull('archived_at')
            ->latest('created_at')
            ->limit(20)
            ->get();

        $unreadCount = NotificationRecipient::query()
            ->where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications->map(function ($recipient) {
                return $this->formatNotification($recipient);
            })->values(),

            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark one notification as read.
     */
    public function markAsRead(
        Request $request,
        NotificationRecipient $notificationRecipient
    ): JsonResponse {
        abort_unless(
            $notificationRecipient->user_id === $request->user()->id,
            403
        );

        if (!$notificationRecipient->read_at) {
            $notificationRecipient->markAsRead();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        NotificationRecipient::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->whereNull('archived_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Format notification for frontend.
     */
    private function formatNotification(
        NotificationRecipient $recipient
    ): array {
        $notification = $recipient->notification;

        return [
            'recipient_id' => $recipient->id,
            'id' => $notification->id,

            'title' => $notification->title,
            'body' => $notification->body,
            'type' => $notification->type,

            'payload' => $notification->payload
                ? $notification->payload->toArray()
                : [],

            'action_url' => $notification->action_url,
            'action_label' => $notification->action_label,

            'status' => $notification->status,

            'read_at' => $recipient->read_at,
            'archived_at' => $recipient->archived_at,

            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}