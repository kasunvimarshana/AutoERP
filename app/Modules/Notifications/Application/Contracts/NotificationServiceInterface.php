<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\Contracts;

use Modules\Notifications\Application\DTOs\CreateNotificationDTO;
use Modules\Notifications\Domain\Entities\Notification;

interface NotificationServiceInterface
{
    public function getById(string $id): Notification;

    /** @return Notification[] */
    public function listByTenant(string $tenantId, string $orgUnitId): array;

    /** @return Notification[] */
    public function listByEntity(string $tenantId, string $entityType, string $entityId): array;

    /** @return Notification[] */
    public function listUnread(string $tenantId, string $orgUnitId): array;

    public function create(CreateNotificationDTO $dto): Notification;

    public function markRead(string $id): Notification;

    public function delete(string $id): void;
}
