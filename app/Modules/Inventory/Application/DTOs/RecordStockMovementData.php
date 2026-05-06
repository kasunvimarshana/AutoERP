<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

class RecordStockMovementData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $productId,
        public readonly ?int $variantId,
        public readonly ?int $batchId,
        public readonly ?int $serialId,
        public readonly ?int $fromLocationId,
        public readonly ?int $toLocationId,
        public readonly string $movementType,
        public readonly ?string $referenceType,
        public readonly ?int $referenceId,
        public readonly int $uomId,
        public readonly string $quantity,
        public readonly ?string $unitCost,
        public readonly ?int $performedBy,
        public readonly ?string $performedAt,
        public readonly ?string $notes,
        public readonly ?array $metadata,
    ) {}
}
