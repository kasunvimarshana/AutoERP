<?php
namespace Modules\Inventory\Domain\Entities;
class StockMovement
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $type,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly ?string $fromLocationId,
        public readonly ?string $toLocationId,
        public readonly string $qty,
        public readonly string $unitCost,
        public readonly ?string $referenceType,
        public readonly ?string $referenceId,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly \DateTimeImmutable $postedAt,
    ) {}
}
