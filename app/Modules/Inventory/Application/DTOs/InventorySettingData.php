<?php
namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class InventorySettingData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $valuationMethod,
        public readonly string $managementMethod,
        public readonly string $stockRotationStrategy,
        public readonly string $allocationAlgorithm,
        public readonly string $cycleCountMethod,
        public readonly bool $negativeStockAllowed = false,
        public readonly bool $autoReorderEnabled = false,
        public readonly ?float $defaultReorderPoint = null,
        public readonly ?float $defaultReorderQty = null,
    ) {}
}
