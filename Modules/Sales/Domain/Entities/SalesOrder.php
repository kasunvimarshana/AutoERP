<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

final class SalesOrder
{
    /**
     * @param  SalesOrderLine[]  $lines
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $orderNumber,
        public readonly string $customerName,
        public readonly ?string $customerEmail,
        public readonly ?string $customerPhone,
        public readonly string $status,
        public readonly string $orderDate,
        public readonly ?string $dueDate,
        public readonly ?string $notes,
        public readonly string $currency,
        public readonly string $subtotal,
        public readonly string $taxAmount,
        public readonly string $discountAmount,
        public readonly string $totalAmount,
        public readonly array $lines,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
