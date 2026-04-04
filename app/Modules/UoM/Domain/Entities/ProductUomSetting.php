<?php
namespace Modules\UoM\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class ProductUomSetting extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $productId,
        public readonly int $baseUomId,
        public readonly ?int $purchaseUomId = null,
        public readonly ?int $salesUomId = null,
        public readonly ?int $inventoryUomId = null,
        public readonly float $purchaseFactor = 1.0,
        public readonly float $salesFactor = 1.0,
        public readonly float $inventoryFactor = 1.0,
    ) {
        parent::__construct($id);
    }
}
