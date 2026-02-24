<?php

namespace Modules\Purchase\Domain\Entities;

class PurchaseRequisitionLine
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $requisitionId,
        public readonly string  $productId,
        public readonly string  $qty,
        public readonly string  $unitPrice,
        public readonly string  $lineTotal,
        public readonly ?string $uom,
        public readonly ?string $requiredByDate,
        public readonly ?string $justification,
        public readonly int     $sortOrder,
    ) {}
}
