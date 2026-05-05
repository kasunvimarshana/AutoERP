<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Notifications\Domain\Entities\Notification;
use Modules\Notifications\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\EntityType;
use Modules\Notifications\Domain\ValueObjects\NotificationChannel;
use Modules\Notifications\Domain\ValueObjects\NotificationStatus;
use Modules\Notifications\Domain\ValueObjects\NotificationType;
use Modules\Notifications\Domain\ValueObjects\RecipientType;
use Modules\Notifications\Infrastructure\Persistence\Eloquent\Models\NotificationModel;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function findById(string $id): ?Notification
    {
        $model = NotificationModel::withoutGlobalScope('tenant')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(string $tenantId, string $orgUnitId): array
    {
        return NotificationModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (NotificationModel $m) => $this->toEntity($m))
            ->all();
    }

    public function findByEntity(string $tenantId, string $entityType, string $entityId): array
    {
        return NotificationModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (NotificationModel $m) => $this->toEntity($m))
            ->all();
    }

    public function findUnread(string $tenantId, string $orgUnitId): array
    {
        return NotificationModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->whereNotIn('status', ['read', 'failed'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (NotificationModel $m) => $this->toEntity($m))
            ->all();
    }

    public function save(Notification $notification): Notification
    {
        $model = NotificationModel::withoutGlobalScope('tenant')
            ->firstOrNew(['id' => $notification->id]);

        $model->fill([
            'id'                  => $notification->id,
            'tenant_id'           => $notification->tenantId,
            'org_unit_id'         => $notification->orgUnitId,
            'row_version'         => $notification->rowVersion,
            'notification_number' => $notification->notificationNumber,
            'notification_type'   => $notification->notificationType->value,
            'entity_type'         => $notification->entityType->value,
            'entity_id'           => $notification->entityId,
            'recipient_type'      => $notification->recipientType->value,
            'recipient_id'        => $notification->recipientId,
            'title'               => $notification->title,
            'message'             => $notification->message,
            'channel'             => $notification->channel->value,
            'status'              => $notification->status->value,
            'sent_at'             => $notification->sentAt?->format('Y-m-d H:i:s'),
            'read_at'             => $notification->readAt?->format('Y-m-d H:i:s'),
            'failed_reason'       => $notification->failedReason,
            'metadata'            => $notification->metadata,
            'is_active'           => $notification->isActive,
        ]);

        $model->save();

        return $this->toEntity($model->fresh());
    }

    public function markRead(string $id): Notification
    {
        $model = NotificationModel::withoutGlobalScope('tenant')->findOrFail($id);
        $model->update([
            'status'  => NotificationStatus::Read->value,
            'read_at' => now()->format('Y-m-d H:i:s'),
        ]);
        $model->increment('row_version');

        return $this->toEntity($model->fresh());
    }

    public function delete(string $id): void
    {
        NotificationModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->delete();
    }

    private function toEntity(NotificationModel $model): Notification
    {
        return new Notification(
            id:                 $model->id,
            tenantId:           $model->tenant_id,
            orgUnitId:          $model->org_unit_id,
            rowVersion:         (int) $model->row_version,
            notificationNumber: $model->notification_number,
            notificationType:   NotificationType::from($model->notification_type),
            entityType:         EntityType::from($model->entity_type),
            entityId:           $model->entity_id,
            recipientType:      RecipientType::from($model->recipient_type),
            recipientId:        $model->recipient_id,
            title:              $model->title,
            message:            $model->message,
            channel:            NotificationChannel::from($model->channel),
            status:             NotificationStatus::from($model->status),
            sentAt:             $model->sent_at
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->sent_at->format('Y-m-d H:i:s'))
                : null,
            readAt:             $model->read_at
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->read_at->format('Y-m-d H:i:s'))
                : null,
            failedReason:       $model->failed_reason,
            metadata:           $model->metadata,
            isActive:           (bool) $model->is_active,
            createdAt:          DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->created_at->format('Y-m-d H:i:s')),
            updatedAt:          DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->updated_at->format('Y-m-d H:i:s')),
        );
    }
}
