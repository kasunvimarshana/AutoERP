<?php
namespace Modules\Sales\Domain\Entities;
class SalesOrder
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $number,
        public readonly string $customerId,
        public readonly ?string $quotationId,
        public readonly string $status,
        public readonly array $lines,
        public readonly string $totalAmount,
        public readonly string $currency,
        public readonly ?\DateTimeImmutable $promisedDeliveryDate,
    ) {}
}
