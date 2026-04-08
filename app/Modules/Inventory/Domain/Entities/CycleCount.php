<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class CycleCount
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $countNumber,
        public readonly string $warehouseId,
        public readonly ?string $locationId,
        public readonly string $status,
        public readonly string $countedAt,
        public readonly ?string $completedAt,
        public readonly int $countedBy,
        public readonly ?string $notes,
    ) {}
}
