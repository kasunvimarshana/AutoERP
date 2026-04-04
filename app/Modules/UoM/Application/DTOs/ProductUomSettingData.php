<?php
namespace Modules\UoM\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class ProductUomSettingData extends BaseDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $baseUomId,
        public readonly ?int $purchaseUomId = null,
        public readonly ?int $salesUomId = null,
        public readonly ?int $inventoryUomId = null,
        public readonly float $purchaseFactor = 1.0,
        public readonly float $salesFactor = 1.0,
        public readonly float $inventoryFactor = 1.0,
    ) {}
}
