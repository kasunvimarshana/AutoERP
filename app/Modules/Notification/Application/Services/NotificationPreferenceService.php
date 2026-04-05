<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Services;

use Modules\Notification\Application\Contracts\NotificationPreferenceServiceInterface;
use Modules\Notification\Domain\Entities\NotificationPreference;
use Modules\Notification\Domain\RepositoryInterfaces\NotificationPreferenceRepositoryInterface;
use Modules\Notification\Domain\ValueObjects\NotificationChannel;

class NotificationPreferenceService implements NotificationPreferenceServiceInterface
{
    public function __construct(
        private readonly NotificationPreferenceRepositoryInterface $preferences,
    ) {}

    /** @return NotificationPreference[] */
    public function listForUser(int $tenantId, int $userId): array
    {
        return $this->preferences->findAllByUser($tenantId, $userId);
    }

    public function setPreference(
        int    $tenantId,
        int    $userId,
        string $notificationType,
        string $channel,
        bool   $enabled,
    ): NotificationPreference {
        // Validate channel value
        NotificationChannel::fromString($channel);

        $existing = $this->preferences->findByUserAndType(
            $tenantId, $userId, $notificationType, $channel
        );

        if ($existing !== null) {
            $enabled ? $existing->enable() : $existing->disable();
            return $this->preferences->save($existing);
        }

        $preference = new NotificationPreference(
            null,
            $tenantId,
            $userId,
            $notificationType,
            $channel,
            $enabled,
            new \DateTime(),
            new \DateTime(),
        );

        return $this->preferences->save($preference);
    }

    public function isEnabled(
        int    $tenantId,
        int    $userId,
        string $notificationType,
        string $channel,
    ): bool {
        $preference = $this->preferences->findByUserAndType(
            $tenantId, $userId, $notificationType, $channel
        );

        // Default to enabled when no explicit preference has been set
        return $preference === null || $preference->isEnabled();
    }
}
