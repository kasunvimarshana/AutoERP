<?php

namespace Modules\Dispatch\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Dispatch extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $salesOrderId,
        public readonly int $warehouseId,
        public readonly string $dispatchNumber,
        public readonly string $status,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $carrier = null,
        public readonly ?string $shippingAddress = null,
        public readonly ?string $notes = null,
        public readonly ?\DateTimeImmutable $dispatchedAt = null,
        public readonly ?\DateTimeImmutable $deliveredAt = null,
        public readonly ?int $dispatchedBy = null,
    ) {
        parent::__construct($id);
    }
}
