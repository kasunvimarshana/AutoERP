<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

class SalesOrderConfirmed
{
    /**
     * @param list<array{product_id: int, variant_id: int|null, quantity: string, uom_id: int}> $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $salesOrderId,
        public readonly int $customerId,
        public readonly int $warehouseId,
        public readonly array $lines = [],
    ) {}
}
