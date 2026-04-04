<?php
namespace Modules\Inventory\Application\DTOs;

class ConsumeValuationLayersData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly string $valuationMethod,
        public readonly float $quantity,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
    ) {}
}
