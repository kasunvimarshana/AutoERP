<?php
namespace Modules\StockMovement\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class StockMovement extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly int $locationId,
        public readonly string $movementType,
        public readonly float $quantity,
        public readonly string $referenceNumber,
        public readonly ?int $variantId = null,
        public readonly ?int $batchId = null,
        public readonly ?string $lotNumber = null,
        public readonly ?string $serialNumber = null,
        public readonly ?float $unitCost = null,
        public readonly ?int $relatedMovementId = null,
        public readonly ?string $notes = null,
        public readonly ?\DateTimeImmutable $movedAt = null,
        public readonly ?int $movedBy = null,
    ) {
        parent::__construct($id);
    }
}
