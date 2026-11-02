<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

use Modules\Purchase\Enums\PurchaseReturnType;

/**
 * @param  list<PurchaseReturnLineData>  $lines
 */
final readonly class CreatePurchaseReturnData
{
    public function __construct(
        public int $tenantId,
        public string $returnDate,
        public ?int $warehouseId,
        public ?int $organizationUnitId = null,
        public ?string $returnNumber = null,
        public ?int $warehouseLocationId = null,
        public ?string $supplierType = null,
        public ?int $supplierId = null,
        public ?string $reason = null,
        public PurchaseReturnType $returnType = PurchaseReturnType::Referenced,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public bool $approvalRequired = false,
        public bool $affectsSupplierBalance = true,
        public ?string $costBasis = null,
        public ?array $auditMetadata = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}
