<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class AllocationData
{
    public function __construct(
        public int $tenantId,
        public string $allocationDate,
        public int $itemId,
        public int $warehouseId,
        public string $quantityAllocated,
        public ?int $organizationUnitId = null,
        public ?string $allocationNumber = null,
        public ?int $reservationId = null,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
        public ?int $serialNumberId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
        public ?string $notes = null,
        public ?int $uomId = null,
        public ?int $createdBy = null,
    ) {}
}
