<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

/**
 * @param  list<PurchaseReturnLineData>  $lines
 */
final readonly class CreatePurchaseReturnData
{
    public function __construct(
        public int $tenantId,
        public string $returnDate,
        public int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?string $returnNumber = null,
        public ?int $warehouseLocationId = null,
        public ?string $supplierType = null,
        public ?int $supplierId = null,
        public ?string $reason = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
