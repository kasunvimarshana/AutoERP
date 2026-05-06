<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Events;

class ShipmentProcessed
{
    /**
     * @param  array<int, array{id: int|null, product_id: int, from_location_id: int, uom_id: int, shipped_qty: string, unit_cost: string|null, variant_id: int|null, batch_id: int|null, serial_id: int|null}>  $lines
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $shipmentId,
        public readonly int $customerId,
        public readonly int $warehouseId,
        public readonly ?int $salesOrderId = null,
        public readonly array $lines = [],
    ) {}
}
