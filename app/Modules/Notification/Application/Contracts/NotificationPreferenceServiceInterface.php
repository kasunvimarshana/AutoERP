<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Contracts;

use Modules\Notification\Domain\Entities\NotificationPreference;

interface NotificationPreferenceServiceInterface
{
    /** @return NotificationPreference[] */
    public function listForUser(int $tenantId, int $userId): array;

    public function setPreference(
        int    $tenantId,
        int    $userId,
        string $notificationType,
        string $channel,
        bool   $enabled,
    ): NotificationPreference;

    public function isEnabled(
        int    $tenantId,
        int    $userId,
        string $notificationType,
        string $channel,
    ): bool;
}
