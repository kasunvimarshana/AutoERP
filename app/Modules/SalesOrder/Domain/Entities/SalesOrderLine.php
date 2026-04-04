<?php

namespace Modules\SalesOrder\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class SalesOrderLine extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $salesOrderId,
        public readonly int $productId,
        public readonly float $orderedQty,
        public readonly float $unitPrice,
        public readonly float $lineTotal,
        public readonly ?int $variantId = null,
        public readonly ?float $discountAmount = null,
        public readonly ?string $notes = null,
        public float $fulfilledQty = 0.0,
    ) {
        parent::__construct($id);
    }
}
