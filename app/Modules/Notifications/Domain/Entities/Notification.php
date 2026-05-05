<?php

declare(strict_types=1);

namespace Modules\Notifications\Domain\Entities;

use DateTimeImmutable;
use Modules\Notifications\Domain\ValueObjects\EntityType;
use Modules\Notifications\Domain\ValueObjects\NotificationChannel;
use Modules\Notifications\Domain\ValueObjects\NotificationStatus;
use Modules\Notifications\Domain\ValueObjects\NotificationType;
use Modules\Notifications\Domain\ValueObjects\RecipientType;

class Notification
{
    public function __construct(
        public readonly string              $id,
        public readonly string              $tenantId,
        public readonly string              $orgUnitId,
        public readonly int                 $rowVersion,
        public readonly string              $notificationNumber,
        public readonly NotificationType    $notificationType,
        public readonly EntityType          $entityType,
        public readonly ?string             $entityId,
        public readonly RecipientType       $recipientType,
        public readonly ?string             $recipientId,
        public readonly string              $title,
        public readonly string              $message,
        public readonly NotificationChannel $channel,
        public readonly NotificationStatus  $status,
        public readonly ?DateTimeImmutable  $sentAt,
        public readonly ?DateTimeImmutable  $readAt,
        public readonly ?string             $failedReason,
        public readonly ?array              $metadata,
        public readonly bool                $isActive,
        public readonly DateTimeImmutable   $createdAt,
        public readonly DateTimeImmutable   $updatedAt,
    ) {}
}
