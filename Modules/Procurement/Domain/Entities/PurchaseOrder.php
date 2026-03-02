<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

final class PurchaseOrder
{
    /**
     * @param  PurchaseOrderLine[]  $lines
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $supplierId,
        public readonly string $orderNumber,
        public readonly string $status,
        public readonly string $orderDate,
        public readonly ?string $expectedDeliveryDate,
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

    public function isFullyReceived(): bool
    {
        if (empty($this->lines)) {
            return false;
        }

        foreach ($this->lines as $line) {
            if (! $line->isFullyReceived()) {
                return false;
            }
        }

        return true;
    }
}
