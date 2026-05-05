<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\DTOs;

final class ProcessReturnResult
{
    public function __construct(
        public readonly string $inspectionId,
        public readonly string $refundId,
        public readonly string $refundNumber,
        public readonly string $grossAmount,
        public readonly string $adjustmentAmount,
        public readonly string $netRefundAmount,
        public readonly string $status,
    ) {
    }
}
