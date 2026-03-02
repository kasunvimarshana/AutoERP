<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Carbon;
use Modules\Notification\Domain\Contracts\NotificationRepositoryInterface;
use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;
use Modules\Notification\Infrastructure\Models\NotificationModel;

class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    protected function model(): string
    {
        return NotificationModel::class;
    }

    public function findById(int $id, int $tenantId): ?Notification
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $userId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (NotificationModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findUnread(int $tenantId, int $userId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', '!=', NotificationStatus::Read->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (NotificationModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(Notification $notification): Notification
    {
        if ($notification->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $notification->tenantId)
                ->findOrFail($notification->id);
        } else {
            $model = new NotificationModel;
            $model->tenant_id = $notification->tenantId;
            $model->user_id = $notification->userId;
        }

        $model->channel = $notification->channel->value;
        $model->event_type = $notification->eventType;
        $model->template_id = $notification->templateId;
        $model->subject = $notification->subject;
        $model->body = $notification->body;
        $model->status = $notification->status->value;
        $model->sent_at = $notification->sentAt ? Carbon::parse($notification->sentAt) : null;
        $model->read_at = $notification->readAt ? Carbon::parse($notification->readAt) : null;
        $model->save();

        return $this->toDomain($model);
    }

    public function markRead(int $id, int $tenantId): ?Notification
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        if ($model === null) {
            return null;
        }

        $model->status = NotificationStatus::Read->value;
        $model->read_at = Carbon::now();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toDomain(NotificationModel $model): Notification
    {
        return new Notification(
            id: $model->id,
            tenantId: $model->tenant_id,
            userId: $model->user_id,
            channel: NotificationChannel::from($model->channel),
            eventType: $model->event_type,
            templateId: $model->template_id,
            subject: $model->subject,
            body: $model->body,
            status: NotificationStatus::from($model->status),
            sentAt: $model->sent_at?->toIso8601String(),
            readAt: $model->read_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
