<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create a notification, attach recipients, and broadcast to each.
     *
     * @param array $data  ['title','body','type','payload','action_url','action_label','created_by']
     * @param array|int $recipients User ID or array of user IDs
     * @param Model|null $actionable Optional related model (polymorphic)
     */
    public function send(array $data, $recipients, $actionable = null): Notification
    {
        $userIds = is_array($recipients) ? $recipients : [$recipients];

        $notification = DB::transaction(function () use ($data, $userIds, $actionable) {
            if ($actionable) {
                $data['actionable_type'] = get_class($actionable);
                $data['actionable_id'] = $actionable->id;
            }

            $notification = Notification::create($data);
            $notification->addRecipients($userIds);

            return $notification;
        });

        foreach ($userIds as $userId) {
            broadcast(new NotificationCreated($notification, $userId));
        }

        $notification->markAsSent();

        return $notification;
    }

    public function recipientsForModule(string $module): array
    {
        return User::query()
            ->where('role', 'super_admin')
            ->orWhereJsonContains("permissions->{$module}", 'view')
            ->pluck('id')
            ->all();
    }
}