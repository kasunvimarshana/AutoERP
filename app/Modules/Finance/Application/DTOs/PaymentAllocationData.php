<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class PaymentAllocationData
{
    public function __construct(
        public readonly int $paymentId,
        public readonly string $invoiceType,
        public readonly int $invoiceId,
        public readonly float $allocatedAmount,
        public readonly ?int $tenantId = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentId: (int) $data['payment_id'],
            invoiceType: (string) $data['invoice_type'],
            invoiceId: (int) $data['invoice_id'],
            allocatedAmount: (float) $data['allocated_amount'],
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
