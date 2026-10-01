<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public int $recipientUserId
    ) {}

    /**
     * Broadcast on each recipient's private channel
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->recipientUserId)];
    }

    /**
     * Event name as heard on the frontend
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Control exactly what payload goes over the wire
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'type' => $this->notification->type,
            'payload' => $this->notification->payload,
            'action_url' => $this->notification->action_url,
            'action_label' => $this->notification->action_label,
            'created_at' => $this->notification->created_at->toIso8601String(),
        ];
    }
}