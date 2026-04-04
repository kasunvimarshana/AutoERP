<?php

namespace Modules\SalesOrder\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class SalesOrder extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $customerId,
        public readonly string $soNumber,
        public readonly string $status,
        public readonly ?float $totalAmount = null,
        public readonly ?float $taxAmount = null,
        public readonly ?float $discountAmount = null,
        public readonly ?string $currency = 'USD',
        public readonly ?string $shippingAddress = null,
        public readonly ?string $notes = null,
        public readonly ?\DateTimeImmutable $expectedDeliveryDate = null,
        public readonly ?int $pickedBy = null,
        public readonly ?\DateTimeImmutable $pickedAt = null,
        public readonly ?int $packedBy = null,
        public readonly ?\DateTimeImmutable $packedAt = null,
    ) {
        parent::__construct($id);
    }
}
