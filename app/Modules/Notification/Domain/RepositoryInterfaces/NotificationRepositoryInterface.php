<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\RepositoryInterfaces;

use Modules\Notification\Domain\Entities\Notification;

interface NotificationRepositoryInterface
{
    public function findById(int $id): ?Notification;

    /** @return Notification[] */
    public function findByUser(int $tenantId, int $userId, bool $unreadOnly = false): array;

    /** @return Notification[] */
    public function findByTenant(int $tenantId, int $page = 1, int $perPage = 50): array;

    public function save(Notification $notification): Notification;

    public function delete(int $id): void;

    public function markAllReadForUser(int $tenantId, int $userId, \DateTimeInterface $readAt): void;

    public function countUnreadForUser(int $tenantId, int $userId): int;
}
