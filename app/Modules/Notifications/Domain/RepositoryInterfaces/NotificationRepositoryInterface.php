<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\RepositoryInterfaces;

use Modules\Notifications\Domain\Entities\Notification;

interface NotificationRepositoryInterface
{
    public function findById(string $id): ?Notification;

    /** @return Notification[] */
    public function findByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Notification[] */
    public function findByEntity(string $tenantId, string $entityType, string $entityId): array;

    /** @return Notification[] */
    public function findUnread(string $tenantId, string $orgUnitId): array;

    public function save(Notification $notification): Notification;

    public function markRead(string $id): Notification;

    public function delete(string $id): void;
}
