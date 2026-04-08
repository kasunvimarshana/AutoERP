<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entities;

use DateTimeInterface;

class SalesOrder
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $customerId,
        public readonly string $warehouseId,
        public readonly string $reference,
        public readonly string $status,
        public readonly DateTimeInterface $orderDate,
        public readonly ?DateTimeInterface $expectedDate,
        public readonly ?string $notes,
        public readonly float $totalAmount,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
