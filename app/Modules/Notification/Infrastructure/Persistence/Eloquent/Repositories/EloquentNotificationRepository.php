<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notification\Domain\ValueObjects\NotificationChannel;
use Modules\Notification\Domain\ValueObjects\NotificationStatus;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private readonly NotificationModel $model) {}

    private function toEntity(NotificationModel $m): Notification
    {
        return new Notification(
            $m->id,
            $m->tenant_id,
            $m->user_id,
            $m->type,
            NotificationChannel::fromString($m->channel),
            $m->title,
            $m->body,
            $m->data,
            NotificationStatus::fromString($m->status),
            $m->read_at,
            $m->sent_at,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?Notification
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByUser(int $tenantId, int $userId, bool $unreadOnly = false): array
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function findByTenant(int $tenantId, int $page = 1, int $perPage = 50): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function save(Notification $notification): Notification
    {
        $data = [
            'tenant_id' => $notification->getTenantId(),
            'user_id'   => $notification->getUserId(),
            'type'      => $notification->getType(),
            'channel'   => $notification->getChannel()->getValue(),
            'title'     => $notification->getTitle(),
            'body'      => $notification->getBody(),
            'data'      => $notification->getData(),
            'status'    => $notification->getStatus()->getValue(),
            'read_at'   => $notification->getReadAt(),
            'sent_at'   => $notification->getSentAt(),
        ];

        if ($notification->getId() === null) {
            $m = $this->model->newQuery()->create($data);
        } else {
            $m = $this->model->newQuery()->findOrFail($notification->getId());
            $m->update($data);
            $m = $m->fresh();
        }

        return $this->toEntity($m);
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }

    public function markAllReadForUser(int $tenantId, int $userId, \DateTimeInterface $readAt): void
    {
        $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => $readAt,
                'status'  => NotificationStatus::READ,
            ]);
    }

    public function countUnreadForUser(int $tenantId, int $userId): int
    {
        return (int) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
