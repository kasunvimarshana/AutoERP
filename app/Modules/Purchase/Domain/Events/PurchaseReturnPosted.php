<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Events;

class PurchaseReturnPosted
{
    /**
     * @param  array<int, array{id: int|null, product_id: int, from_location_id: int, uom_id: int, return_qty: string, unit_cost: string, variant_id: int|null, batch_id: int|null, serial_id: int|null}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $purchaseReturnId,
        public readonly int $supplierId,
        public readonly array $lines = [],
    ) {}
}
