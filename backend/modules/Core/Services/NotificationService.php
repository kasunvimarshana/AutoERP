<?php

namespace Modules\Core\Services;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\NotificationPreference;
use Modules\IAM\Models\User;

class NotificationService
{
    /**
     * Send a notification to a user or users.
     *
     * @param User|Collection<User>|array $users
     * @param mixed $notification
     * @return void
     */
    public function send($users, $notification): void
    {
        // Convert single user to collection
        if ($users instanceof User) {
            $users = collect([$users]);
        } elseif (is_array($users)) {
            $users = collect($users);
        }

        // Filter users based on their notification preferences
        $filteredUsers = $users->filter(function ($user) use ($notification) {
            return $this->shouldSendNotification($user, get_class($notification));
        });

        if ($filteredUsers->isNotEmpty()) {
            Notification::send($filteredUsers, $notification);
        }
    }

    /**
     * Check if notification should be sent to user based on preferences.
     */
    protected function shouldSendNotification(User $user, string $notificationType): bool
    {
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->first();

        // If no preference exists, allow by default
        if (!$preference) {
            return true;
        }

        // At least one channel should be enabled
        return $preference->email_enabled ||
               $preference->database_enabled ||
               $preference->broadcast_enabled ||
               $preference->push_enabled;
    }

    /**
     * Get user's notification preferences.
     */
    public function getPreferences(int $userId): Collection
    {
        return NotificationPreference::where('user_id', $userId)->get();
    }

    /**
     * Update notification preference for a user.
     */
    public function updatePreference(
        int $userId,
        string $notificationType,
        array $channels
    ): NotificationPreference {
        return NotificationPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $notificationType,
            ],
            [
                'email_enabled' => $channels['email'] ?? true,
                'database_enabled' => $channels['database'] ?? true,
                'broadcast_enabled' => $channels['broadcast'] ?? true,
                'push_enabled' => $channels['push'] ?? false,
            ]
        );
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): Collection
    {
        return DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notifications for a user with pagination.
     */
    public function getNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        $query = DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $notifications = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'data' => $notifications,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => ceil($total / $perPage),
        ];
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $notificationId): bool
    {
        $notification = DatabaseNotification::find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark multiple notifications as read.
     */
    public function markMultipleAsRead(array $notificationIds): int
    {
        return DatabaseNotification::whereIn('id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(string $notificationId): bool
    {
        $notification = DatabaseNotification::find($notificationId);
        
        if ($notification) {
            return $notification->delete();
        }

        return false;
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get notification statistics for a user.
     */
    public function getStatistics(int $userId): array
    {
        $total = DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->count();

        $unread = $this->getUnreadCount($userId);
        $read = $total - $unread;

        // Group by type
        $byType = DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'byType' => $byType,
        ];
    }
}
