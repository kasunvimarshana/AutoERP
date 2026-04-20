<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class StockMovement
{
    private ?int $id;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly ?int $batchId,
        private readonly ?int $serialId,
        private readonly ?int $fromLocationId,
        private readonly ?int $toLocationId,
        private readonly string $movementType,
        private readonly ?string $referenceType,
        private readonly ?int $referenceId,
        private readonly int $uomId,
        private readonly string $quantity,
        private readonly ?string $unitCost,
        private readonly ?int $performedBy,
        private readonly ?\DateTimeInterface $performedAt,
        private readonly ?string $notes,
        private readonly ?array $metadata,
        ?int $id = null,
    ) {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int { return $this->tenantId; }

    public function getProductId(): int { return $this->productId; }

    public function getVariantId(): ?int { return $this->variantId; }

    public function getBatchId(): ?int { return $this->batchId; }

    public function getSerialId(): ?int { return $this->serialId; }

    public function getFromLocationId(): ?int { return $this->fromLocationId; }

    public function getToLocationId(): ?int { return $this->toLocationId; }

    public function getMovementType(): string { return $this->movementType; }

    public function getReferenceType(): ?string { return $this->referenceType; }

    public function getReferenceId(): ?int { return $this->referenceId; }

    public function getUomId(): int { return $this->uomId; }

    public function getQuantity(): string { return $this->quantity; }

    public function getUnitCost(): ?string { return $this->unitCost; }

    public function getPerformedBy(): ?int { return $this->performedBy; }

    public function getPerformedAt(): ?\DateTimeInterface { return $this->performedAt; }

    public function getNotes(): ?string { return $this->notes; }

    public function getMetadata(): ?array { return $this->metadata; }
}
