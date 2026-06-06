<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

/**
 * @param  list<PurchaseOrderLineData>  $lines
 * @param  list<PurchaseHeaderAdjustmentData>  $adjustments
 */
final readonly class CreatePurchaseOrderData
{
    public function __construct(
        public int $tenantId,
        public string $purchaseOrderDate,
        public ?int $organizationUnitId = null,
        public ?string $purchaseOrderNumber = null,
        public ?string $supplierType = null,
        public ?int $supplierId = null,
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
