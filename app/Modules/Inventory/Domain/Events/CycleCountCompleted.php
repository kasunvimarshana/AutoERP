<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

class CycleCountCompleted
{
    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $cycleCountId,
        public readonly int $warehouseId,
        public readonly ?int $locationId,
        public readonly string $countDate,
        public readonly array $adjustments,
        public readonly int $createdBy,
    ) {}
}
