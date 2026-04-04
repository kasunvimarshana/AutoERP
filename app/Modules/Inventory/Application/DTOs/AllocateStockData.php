<?php
namespace Modules\Inventory\Application\DTOs;

class AllocateStockData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly float $quantity,
        public readonly string $allocationAlgorithm,
    ) {}
}
