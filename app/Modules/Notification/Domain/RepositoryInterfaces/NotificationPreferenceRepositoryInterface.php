<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\RepositoryInterfaces;

use Modules\Notification\Domain\Entities\NotificationPreference;

interface NotificationPreferenceRepositoryInterface
{
    public function findByUserAndType(
        int    $tenantId,
        int    $userId,
        string $notificationType,
        string $channel,
    ): ?NotificationPreference;

    /** @return NotificationPreference[] */
    public function findAllByUser(int $tenantId, int $userId): array;

    public function save(NotificationPreference $preference): NotificationPreference;

    public function delete(int $id): void;
}
