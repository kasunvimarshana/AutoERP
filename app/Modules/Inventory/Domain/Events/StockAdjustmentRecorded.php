<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Events;

class StockAdjustmentRecorded
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $stockMovementId,
        public readonly int $productId,
        public readonly string $movementType,
        public readonly string $quantity,
        public readonly string $unitCost,
        public readonly string $amount,
        public readonly ?int $inventoryAccountId,
        public readonly ?int $expenseAccountId,
        public readonly string $movementDate,
        public readonly int $createdBy,
    ) {}
}
