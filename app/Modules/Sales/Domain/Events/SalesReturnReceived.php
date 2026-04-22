<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

class SalesReturnReceived
{
    /**
     * @param  array<int, array{id: int|null, product_id: int, to_location_id: int, uom_id: int, return_qty: string, variant_id: int|null, batch_id: int|null, serial_id: int|null}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $salesReturnId,
        public readonly int $customerId,
        public readonly array $lines = [],
    ) {}
}
