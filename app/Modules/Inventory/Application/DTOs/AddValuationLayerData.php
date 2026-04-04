<?php
namespace Modules\Inventory\Application\DTOs;

class AddValuationLayerData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly string $valuationMethod,
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly ?int $batchId = null,
        public readonly ?string $receiptDate = null,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
    ) {}
}
