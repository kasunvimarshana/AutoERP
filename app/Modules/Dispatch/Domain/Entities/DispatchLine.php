<?php

namespace Modules\Dispatch\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class DispatchLine extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $dispatchId,
        public readonly int $salesOrderLineId,
        public readonly int $productId,
        public readonly float $dispatchedQty,
        public readonly int $locationId,
        public readonly ?int $variantId = null,
        public readonly ?int $batchId = null,
        public readonly ?string $serialNumber = null,
        public readonly ?string $lotNumber = null,
    ) {
        parent::__construct($id);
    }
}
