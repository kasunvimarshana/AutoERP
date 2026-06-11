<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class CreateSalesOrderData
{
    /**
     * @param  list<SalesLineData>  $lines
     * @param  list<SalesHeaderAdjustmentData>  $adjustments
     */
    public function __construct(
        public int $tenantId,
        public string $salesOrderDate,
        public int $customerId,
        public ?int $organizationUnitId = null,
        public ?string $salesOrderNumber = null,
        public ?int $quotationId = null,
        public ?int $warehouseId = null,
        public ?int $warehouseLocationId = null,
        public ?string $expectedDeliveryDate = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
        public array $adjustments = [],
    ) {}
}
