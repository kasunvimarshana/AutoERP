<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

class SalesRefundRecorded
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $salesInvoiceId,
        public readonly int $customerId,
        public readonly int $refundPaymentId,
        public readonly ?int $arAccountId,
        public readonly int $cashAccountId,
        public readonly string $amount,
        public readonly int $currencyId,
        public readonly string $exchangeRate,
        public readonly string $refundDate,
        public readonly int $createdBy = 0,
    ) {}
}
