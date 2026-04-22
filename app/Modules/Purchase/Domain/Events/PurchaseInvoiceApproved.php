<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseInvoiceApproved
{
    /**
     * @param  list<array{account_id: int|null, line_total: string, tax_amount: string}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseInvoiceId,
        public readonly int $supplierId,
        public readonly ?int $apAccountId = null,
        public readonly string $grandTotal = '0.000000',
        public readonly int $currencyId = 1,
        public readonly string $exchangeRate = '1.000000',
        public readonly string $invoiceDate = '',
        public readonly array $lines = [],
        public readonly int $createdBy = 0,
    ) {}
}
