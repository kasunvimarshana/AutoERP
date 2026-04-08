<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeInterface;

class CycleCount
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $warehouseId,
        public readonly ?string $locationId,
        public readonly string $status,
        public readonly DateTimeInterface $scheduledAt,
        public readonly ?DateTimeInterface $completedAt,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
