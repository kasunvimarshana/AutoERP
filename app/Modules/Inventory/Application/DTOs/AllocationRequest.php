<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final readonly class AllocationRequest
{
    /**
     * @param int[] $preferredBatchIds
     * @param string[] $preferredLotNumbers
     * @param string[] $ruleKeys
     */
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public float $requiredQuantity,
        public ?int $locationId = null,
        public ?int $warehouseId = null,
        public ?int $variantId = null,
        public ?string $allocationMethod = null,
        public array $preferredBatchIds = [],
        public array $preferredLotNumbers = [],
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public bool $persistReservation = true,
        public ?\DateTimeInterface $expiresAt = null,
        public array $metadata = [],
        public array $ruleContext = [],
        public array $ruleKeys = [],
        public ?int $organizationUnitId = null,
    ) {
    }
}
