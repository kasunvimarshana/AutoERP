<?php
namespace Modules\StockMovement\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;
class StockMovementData extends BaseDTO
{
    public function __construct(
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
        public readonly ?string $notes = null,
        public readonly ?int $movedBy = null,
    ) {}
}
