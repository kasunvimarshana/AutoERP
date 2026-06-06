<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;

final readonly class StockMovementData
{
    public function __construct(
        public int $tenantId,
        public string $movementDate,
        public InventoryMovementType $movementType,
        public InventoryDirection $direction,
        public int $itemId,
        public int $warehouseId,
        public string $quantity,
        public ?int $organizationUnitId = null,
        public ?string $movementNumber = null,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
        public ?int $serialNumberId = null,
        public string $unitCost = '0.000000',
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
        public ?string $description = null,
        public ?int $createdBy = null,
    ) {}
}
