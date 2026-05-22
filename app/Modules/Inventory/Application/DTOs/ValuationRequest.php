<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final readonly class ValuationRequest
{
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public int $locationId,
        public int $uomId,
        public string $direction,
        public float $quantity,
        public ?int $warehouseId = null,
        public ?int $organizationUnitId = null,
        public ?int $variantId = null,
        public ?int $batchId = null,
        public ?int $serialId = null,
        public ?float $unitCost = null,
        public ?string $txnType = null,
        public ?int $performedBy = null,
        public ?\DateTimeInterface $performedAt = null,
        public ?string $notes = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public array $metadata = [],
        public ?string $valuationMethod = null,
        public ?\DateTimeInterface $layerDate = null,
    ) {
    }
}
