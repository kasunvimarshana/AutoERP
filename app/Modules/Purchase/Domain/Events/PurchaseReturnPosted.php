<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseReturnPosted
{
    /**
     * @param  list<array{id: int|null, product_id: int, from_location_id: int, uom_id: int, return_qty: string, unit_cost: string, variant_id: int|null, batch_id: int|null, serial_id: int|null, account_id: int|null, line_total: string, tax_amount: string}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseReturnId,
        public readonly int $supplierId,
        public readonly ?int $apAccountId = null,
        public readonly string $grandTotal = '0.000000',
        public readonly int $currencyId = 1,
        public readonly string $exchangeRate = '1.000000',
        public readonly string $returnDate = '',
        public readonly array $lines = [],
        public readonly int $createdBy = 0,
    ) {}
}
