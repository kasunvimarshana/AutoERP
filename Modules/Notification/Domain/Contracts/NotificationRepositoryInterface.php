<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Contracts;

use Modules\Notification\Domain\Entities\Notification;

interface NotificationRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Notification;

    public function findAll(int $tenantId, int $userId, int $page, int $perPage): array;

    public function findUnread(int $tenantId, int $userId): array;

    public function save(Notification $notification): Notification;

    public function markRead(int $id, int $tenantId): ?Notification;

    public function delete(int $id, int $tenantId): void;
}
