<?php

namespace Modules\Notification\Infrastructure\Repositories;

use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Infrastructure\Models\NotificationRecordModel;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private NotificationRecordModel $model) {}

    public function paginateForUser(string $userId, int $perPage = 20): object
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findById(string $id): ?object
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByIdAndUser(string $id, string $userId): ?object
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->find($id);
    }

    public function markRead(string $id): void
    {
        $this->model->newQuery()
            ->findOrFail($id)
            ->update(['read_at' => now(), 'status' => 'read']);
    }

    public function markAllReadForUser(string $userId): void
    {
        $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => 'read']);
    }

    public function countUnreadForUser(string $userId): int
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function delete(string $id): void
    {
        $this->model->newQuery()->find($id)?->delete();
    }
}
