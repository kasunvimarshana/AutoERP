<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class PaymentAllocationData
{
    public function __construct(
        public readonly int $payment_id,
        public readonly string $invoice_type,
        public readonly int $invoice_id,
        public readonly float $allocated_amount,
        public readonly ?int $tenant_id = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            payment_id: (int) $data['payment_id'],
            invoice_type: (string) $data['invoice_type'],
            invoice_id: (int) $data['invoice_id'],
            allocated_amount: (float) $data['allocated_amount'],
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
