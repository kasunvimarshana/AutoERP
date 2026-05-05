<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Domain\Events;

class ReturnRefundDrafted
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $rentalTransactionId,
        public readonly string $refundId,
        public readonly string $refundNumber,
        public readonly string $grossAmount,
        public readonly string $adjustmentAmount,
        public readonly string $netRefundAmount,
    ) {
    }
}
