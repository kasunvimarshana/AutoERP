<?php

namespace Modules\Returns\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class StockReturn extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $returnNumber,
        public readonly string $returnType,
        public readonly string $status,
        public readonly ?int $originalOrderId = null,
        public readonly ?string $originalOrderType = null,
        public readonly ?int $customerId = null,
        public readonly ?int $supplierId = null,
        public readonly ?string $reason = null,
        public readonly ?float $totalAmount = null,
        public readonly ?float $restockingFee = null,
        public readonly ?string $creditMemoNumber = null,
        public readonly ?string $notes = null,
        public readonly ?int $approvedBy = null,
        public readonly ?\DateTimeImmutable $approvedAt = null,
        public readonly ?int $completedBy = null,
        public readonly ?\DateTimeImmutable $completedAt = null,
    ) {
        parent::__construct($id);
    }
}
