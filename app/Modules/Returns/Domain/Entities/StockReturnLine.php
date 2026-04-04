<?php

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class StockReturnLine extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $stockReturnId,
        public readonly int $productId,
        public readonly float $returnQty,
        public readonly string $condition,
        public readonly string $qualityCheckResult,
        public readonly int $locationId,
        public readonly ?int $variantId = null,
        public readonly ?int $originalBatchId = null,
        public readonly ?string $originalLotNumber = null,
        public readonly ?string $originalSerialNumber = null,
        public readonly ?float $unitPrice = null,
        public readonly ?float $lineTotal = null,
        public readonly ?string $restockAction = null,
        public readonly ?string $notes = null,
        public readonly ?int $qualityCheckedBy = null,
        public readonly ?\DateTimeImmutable $qualityCheckedAt = null,
    ) {
        parent::__construct($id);
    }
}
