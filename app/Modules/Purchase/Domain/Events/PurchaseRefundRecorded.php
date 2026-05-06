<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseRefundRecorded
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseInvoiceId,
        public readonly int $supplierId,
        public readonly int $refundPaymentId,
        public readonly ?int $apAccountId,
        public readonly int $cashAccountId,
        public readonly string $amount,
        public readonly int $currencyId,
        public readonly string $exchangeRate,
        public readonly string $refundDate,
        public readonly int $createdBy = 0,
    ) {}
}
