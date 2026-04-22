<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

class SalesPaymentRecorded
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $salesInvoiceId,
        public readonly int $customerId,
        public readonly int $paymentId,
        public readonly ?int $arAccountId,
        public readonly int $cashAccountId,
        public readonly string $amount,
        public readonly int $currencyId,
        public readonly string $exchangeRate,
        public readonly string $paymentDate,
        public readonly int $createdBy = 0,
    ) {}
}
