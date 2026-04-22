<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

class SalesInvoicePosted
{
    /**
     * @param  list<array{income_account_id: int|null, line_total: string, tax_amount: string}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $salesInvoiceId,
        public readonly int $customerId,
        public readonly ?int $arAccountId = null,
        public readonly string $grandTotal = '0.000000',
        public readonly int $currencyId = 1,
        public readonly string $exchangeRate = '1.000000',
        public readonly string $invoiceDate = '',
        public readonly array $lines = [],
        public readonly int $createdBy = 0,
    ) {}
}
