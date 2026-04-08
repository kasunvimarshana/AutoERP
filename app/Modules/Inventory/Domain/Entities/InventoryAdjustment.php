<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryAdjustment
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $adjustmentNumber,
        public readonly string $adjustmentDate,
        public readonly string $warehouseId,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $reason,
    ) {}
}
