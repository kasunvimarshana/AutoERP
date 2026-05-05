<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Services;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Application\Contracts\NotificationServiceInterface;
use Modules\Notifications\Application\DTOs\CreateNotificationDTO;
use Modules\Notifications\Domain\Entities\Notification;
use Modules\Notifications\Domain\Exceptions\NotificationNotFoundException;
use Modules\Notifications\Domain\RepositoryInterfaces\NotificationRepositoryInterface;
use Modules\Notifications\Domain\ValueObjects\NotificationStatus;
use Ramsey\Uuid\Uuid;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository,
    ) {}

    public function getById(string $id): Notification
    {
        $notification = $this->repository->findById($id);
        if ($notification === null) {
            throw new NotificationNotFoundException($id);
        }

        return $notification;
    }

    public function listByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findByTenant($tenantId, $orgUnitId);
    }

    public function listByEntity(string $tenantId, string $entityType, string $entityId): array
    {
        return $this->repository->findByEntity($tenantId, $entityType, $entityId);
    }

    public function listUnread(string $tenantId, string $orgUnitId): array
    {
        return $this->repository->findUnread($tenantId, $orgUnitId);
    }

    public function create(CreateNotificationDTO $dto): Notification
    {
        return DB::transaction(function () use ($dto): Notification {
            $now    = new DateTimeImmutable();
            $entity = new Notification(
                id:                 Uuid::uuid4()->toString(),
                tenantId:           $dto->tenantId,
                orgUnitId:          $dto->orgUnitId,
                rowVersion:         1,
                notificationNumber: $dto->notificationNumber,
                notificationType:   $dto->notificationType,
                entityType:         $dto->entityType,
                entityId:           $dto->entityId,
                recipientType:      $dto->recipientType,
                recipientId:        $dto->recipientId,
                title:              $dto->title,
                message:            $dto->message,
                channel:            $dto->channel,
                status:             NotificationStatus::Pending,
                sentAt:             null,
                readAt:             null,
                failedReason:       null,
                metadata:           $dto->metadata,
                isActive:           true,
                createdAt:          $now,
                updatedAt:          $now,
            );

            return $this->repository->save($entity);
        });
    }

    public function markRead(string $id): Notification
    {
        return DB::transaction(function () use ($id): Notification {
            $this->getById($id);

            return $this->repository->markRead($id);
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->getById($id);
            $this->repository->delete($id);
        });
    }
}
