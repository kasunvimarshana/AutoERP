<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationStatus;

final class Notification
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly NotificationChannel $channel,
        public readonly string $eventType,
        public readonly ?int $templateId,
        public readonly string $subject,
        public readonly string $body,
        public readonly NotificationStatus $status,
        public readonly ?string $sentAt,
        public readonly ?string $readAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
