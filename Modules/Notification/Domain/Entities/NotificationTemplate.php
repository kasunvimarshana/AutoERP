<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

use Modules\Notification\Domain\Enums\NotificationChannel;

final class NotificationTemplate
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly NotificationChannel $channel,
        public readonly string $eventType,
        public readonly string $name,
        public readonly string $subject,
        public readonly string $body,
        public readonly bool $isActive,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
