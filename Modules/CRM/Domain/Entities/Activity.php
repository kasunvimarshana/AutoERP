<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Entities;

use Modules\Crm\Domain\Enums\ActivityType;

final class Activity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $contactId,
        public readonly ?int $leadId,
        public readonly ActivityType $type,
        public readonly string $subject,
        public readonly ?string $description,
        public readonly ?string $scheduledAt,
        public readonly ?string $completedAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
