<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Inventory\Domain\Enums\StockMovementDirection;

final readonly class MovementLineDTO
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $itemId,
        public ?int $variantId,
        public ?int $warehouseId,
        public ?int $locationId,
        public ?int $batchId,
        public ?int $serialId,
        public int $uomId,
        public float $quantity,
        public StockMovementDirection $direction,
        public string $txnType,
        public ?float $providedUnitCost = null,
        public ?int $performedBy = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?string $notes = null,
        public ?int $currencyId = null,
        public ?float $exchangeRate = null,
        public array $metadata = [],
    ) {
    }

    public function withQuantityAndUom(float $quantity, int $uomId): self
    {
        return new self(
            tenantId: $this->tenantId,
            organizationUnitId: $this->organizationUnitId,
            itemId: $this->itemId,
            variantId: $this->variantId,
            warehouseId: $this->warehouseId,
            locationId: $this->locationId,
            batchId: $this->batchId,
            serialId: $this->serialId,
            uomId: $uomId,
            quantity: $quantity,
            direction: $this->direction,
            txnType: $this->txnType,
            providedUnitCost: $this->providedUnitCost,
            performedBy: $this->performedBy,
            referenceType: $this->referenceType,
            referenceId: $this->referenceId,
            notes: $this->notes,
            currencyId: $this->currencyId,
            exchangeRate: $this->exchangeRate,
            metadata: $this->metadata,
        );
    }
}
