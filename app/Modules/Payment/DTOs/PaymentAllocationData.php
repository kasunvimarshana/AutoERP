<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentAllocationMethod;

final readonly class PaymentAllocationData
{
    public function __construct(
        public int $invoiceId,
        public string $allocatedAmount,
        public string $allocationDate,
        public bool $allowOverpayment = false,
        public string $allocationMethod = PaymentAllocationMethod::SpecificInvoice->value,
        public ?array $metadata = null,
    ) {}
}
