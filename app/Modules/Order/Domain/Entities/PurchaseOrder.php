<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entities;

class PurchaseOrder
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $orderNumber,
        public readonly string $orderDate,
        public readonly string $supplierId,
        public readonly string $status,
        public readonly string $currencyCode,
        public readonly float $totalAmount,
        public readonly float $paidAmount,
        public readonly string $paymentStatus,
    ) {}
}
