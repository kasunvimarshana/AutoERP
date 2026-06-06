<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

final readonly class PaymentAllocationData
{
    public function __construct(
        public int $invoiceId,
        public string $allocatedAmount,
        public string $allocationDate,
        public bool $allowOverpayment = false,
    ) {}
}
