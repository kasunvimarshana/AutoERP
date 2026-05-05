<?php

declare(strict_types=1);

namespace Modules\Notifications\Application\DTOs;

use Modules\Notifications\Domain\ValueObjects\EntityType;
use Modules\Notifications\Domain\ValueObjects\NotificationChannel;
use Modules\Notifications\Domain\ValueObjects\NotificationType;
use Modules\Notifications\Domain\ValueObjects\RecipientType;

class CreateNotificationDTO
{
    public function __construct(
        public readonly string              $tenantId,
        public readonly string              $orgUnitId,
        public readonly string              $notificationNumber,
        public readonly NotificationType    $notificationType,
        public readonly EntityType          $entityType,
        public readonly ?string             $entityId,
        public readonly RecipientType       $recipientType,
        public readonly ?string             $recipientId,
        public readonly string              $title,
        public readonly string              $message,
        public readonly NotificationChannel $channel,
        public readonly ?array              $metadata,
    ) {}
}
