<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class ReservationData
{
    public function __construct(
        public int $tenantId,
        public string $reservationDate,
        public int $itemId,
        public int $warehouseId,
        public string $quantityReserved,
        public ?int $organizationUnitId = null,
        public ?string $reservationNumber = null,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
        public ?string $expiresAt = null,
        public ?string $notes = null,
        public ?int $uomId = null,
        public ?int $createdBy = null,
    ) {}
}
