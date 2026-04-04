<?php
namespace Modules\PurchaseOrder\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;
class PurchaseOrderLine extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $purchaseOrderId,
        public readonly int $productId,
        public readonly float $orderedQty,
        public readonly float $unitCost,
        public readonly float $lineTotal,
        public readonly ?int $variantId = null,
        public readonly ?string $notes = null,
        public float $receivedQty = 0.0,
    ) {
        parent::__construct($id);
    }
}
