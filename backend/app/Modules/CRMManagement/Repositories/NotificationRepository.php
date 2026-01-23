<?php

namespace App\Modules\CRMManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\CRMManagement\Models\Notification;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    /**
     * Search notifications by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['notification_type'])) {
            $query->where('notification_type', $criteria['notification_type']);
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (!empty($criteria['priority'])) {
            $query->where('priority', $criteria['priority']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Get notifications by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('notification_type', $type)->with(['user'])->get();
    }

    /**
     * Get notifications by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['user'])->get();
    }

    /**
     * Get notifications for user
     */
    public function getForUser(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread notifications
     */
    public function getUnread()
    {
        return $this->model->where('status', 'unread')->with(['user'])->get();
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadForUser(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'unread')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get read notifications
     */
    public function getRead()
    {
        return $this->model->where('status', 'read')->with(['user'])->get();
    }

    /**
     * Get notifications by priority
     */
    public function getByPriority(string $priority)
    {
        return $this->model->where('priority', $priority)->with(['user'])->get();
    }

    /**
     * Get high priority notifications
     */
    public function getHighPriority()
    {
        return $this->model->whereIn('priority', ['high', 'urgent'])
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark as read
     */
    public function markAsRead(int $id): bool
    {
        $notification = $this->findOrFail($id);
        return $notification->update(['status' => 'read', 'read_at' => now()]);
    }

    /**
     * Mark multiple as read
     */
    public function markMultipleAsRead(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }
}
