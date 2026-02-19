<?php

declare(strict_types=1);

namespace Modules\Notification\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Notification\Models\NotificationLog;

/**
 * Notification Log Repository
 *
 * Handles data access for notification logs
 */
class NotificationLogRepository extends BaseRepository
{
    public function __construct(NotificationLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Get logs for a notification
     */
    public function getByNotification(int $notificationId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('notification_id', $notificationId);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get logs for a user
     */
    public function getByUser(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Delete old logs
     */
    public function deleteOlderThan(int $days): int
    {
        return $this->model
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
