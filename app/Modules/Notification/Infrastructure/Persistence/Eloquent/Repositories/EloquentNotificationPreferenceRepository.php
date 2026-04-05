<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Notification\Domain\Entities\NotificationPreference;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationPreferenceRepositoryInterface;
use Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationPreferenceModel;

class EloquentNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(private readonly NotificationPreferenceModel $model) {}

    private function toEntity(NotificationPreferenceModel $m): NotificationPreference
    {
        return new NotificationPreference(
            $m->id,
            $m->tenant_id,
            $m->user_id,
            $m->notification_type,
            $m->channel,
            (bool) $m->enabled,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findByUserAndType(
        int    $tenantId,
        int    $userId,
        string $notificationType,
        string $channel,
    ): ?NotificationPreference {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('notification_type', $notificationType)
            ->where('channel', $channel)
            ->first();

        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByUser(int $tenantId, int $userId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function save(NotificationPreference $preference): NotificationPreference
    {
        $data = [
            'tenant_id'         => $preference->getTenantId(),
            'user_id'           => $preference->getUserId(),
            'notification_type' => $preference->getNotificationType(),
            'channel'           => $preference->getChannel(),
            'enabled'           => $preference->isEnabled(),
        ];

        if ($preference->getId() === null) {
            $m = $this->model->newQuery()->create($data);
        } else {
            $m = $this->model->newQuery()->findOrFail($preference->getId());
            $m->update($data);
            $m = $m->fresh();
        }

        return $this->toEntity($m);
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}
