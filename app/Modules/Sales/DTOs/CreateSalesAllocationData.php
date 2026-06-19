<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class CreateSalesAllocationData
{
    /**
     * @param  list<SalesAllocationLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $allocationDate,
        public int $salesOrderId,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?string $allocationNumber = null,
        public ?int $warehouseLocationId = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
