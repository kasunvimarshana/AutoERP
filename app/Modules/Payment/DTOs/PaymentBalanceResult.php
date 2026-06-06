<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\UnappliedBalanceStatus;

final readonly class PaymentBalanceResult
{
    public function __construct(
        public string $originalAmount,
        public string $allocatedAmount,
        public string $refundedAmount,
        public string $remainingAmount,
        public UnappliedBalanceStatus $status,
    ) {}
}
