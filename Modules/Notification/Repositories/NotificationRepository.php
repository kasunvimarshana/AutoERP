<?php

declare(strict_types=1);

namespace Modules\Notification\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Notification\Enums\NotificationStatus;
use Modules\Notification\Exceptions\NotificationNotFoundException;
use Modules\Notification\Models\Notification;

/**
 * Notification Repository
 *
 * Handles data access for notifications
 */
class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    /**
     * Find notification by ID
     *
     * @throws NotificationNotFoundException
     */
    public function findById(int $id): Notification
    {
        $notification = $this->model->find($id);

        if (! $notification) {
            throw new NotificationNotFoundException("Notification with ID {$id} not found");
        }

        return $notification;
    }

    /**
     * Get notifications for a specific user
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

        if (isset($filters['unread']) && $filters['unread']) {
            $query->whereNull('read_at');
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadByUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnreadByUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsReadByUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'status' => NotificationStatus::READ,
                'read_at' => now(),
            ]);
    }

    /**
     * Get pending notifications to send
     */
    public function getPending(int $limit = 100): Collection
    {
        return $this->model
            ->where('status', NotificationStatus::PENDING)
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->oldest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed notifications that can be retried
     */
    public function getRetryable(): Collection
    {
        return $this->model
            ->where('status', NotificationStatus::FAILED)
            ->whereColumn('retry_count', '<', 'max_retries')
            ->oldest('failed_at')
            ->limit(50)
            ->get();
    }

    /**
     * Delete old notifications
     */
    public function deleteOlderThan(int $days): int
    {
        return $this->model
            ->where('created_at', '<', now()->subDays($days))
            ->whereIn('status', [NotificationStatus::SENT, NotificationStatus::DELIVERED, NotificationStatus::READ])
            ->delete();
    }
}
