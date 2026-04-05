<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Contracts;

use Modules\Notification\Domain\Entities\Notification;

interface NotificationServiceInterface
{
    /** @return Notification[] */
    public function listForUser(int $tenantId, int $userId, bool $unreadOnly = false): array;

    public function getById(int $id): Notification;

    public function markAsRead(int $id): Notification;

    public function markAllRead(int $tenantId, int $userId): void;

    public function countUnread(int $tenantId, int $userId): int;

    public function delete(int $id): void;
}
