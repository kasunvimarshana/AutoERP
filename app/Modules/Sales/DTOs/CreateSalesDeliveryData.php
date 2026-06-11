<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class CreateSalesDeliveryData
{
    /**
     * @param  list<SalesDeliveryLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $deliveryDate,
        public int $customerId,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?string $deliveryNumber = null,
        public ?int $salesOrderId = null,
        public ?int $warehouseLocationId = null,
        public ?string $notes = null,
        public ?int $deliveredBy = null,
        public array $lines = [],
    ) {}
}
