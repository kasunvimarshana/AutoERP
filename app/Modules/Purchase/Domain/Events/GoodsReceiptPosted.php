<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class GoodsReceiptPosted
{
    /**
     * @param  array<int, array{id: int|null, product_id: int, location_id: int, uom_id: int, received_qty: string, unit_cost: string, variant_id: int|null, batch_id: int|null, serial_id: int|null, inventory_account_id: int|null}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $grnHeaderId,
        public readonly int $supplierId,
        public readonly int $warehouseId,
        public readonly array $lines,
        public readonly ?int $apAccountId = null,
        public readonly int $currencyId = 1,
        public readonly string $exchangeRate = '1.000000',
        public readonly string $receivedDate = '',
        public readonly int $createdBy = 0,
    ) {}
}
