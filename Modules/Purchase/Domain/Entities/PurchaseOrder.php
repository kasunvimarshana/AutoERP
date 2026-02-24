<?php
namespace Modules\Purchase\Domain\Entities;
class PurchaseOrder
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $number,
        public readonly string $vendorId,
        public readonly string $status,
        public readonly array $lines,
        public readonly string $subtotal,
        public readonly string $taxTotal,
        public readonly string $total,
        public readonly string $currency,
        public readonly ?\DateTimeImmutable $deliveryDate,
    ) {}
}
