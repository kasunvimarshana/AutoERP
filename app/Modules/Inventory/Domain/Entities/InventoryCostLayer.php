<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

/**
 * Represents a single cost layer in the inventory valuation stack.
 *
 * Cost layers form the basis of FIFO, LIFO, FEFO, and specific-identification
 * valuation.  For weighted-average costing a single "running" layer is kept
 * per product/variant/location combination and is updated on every receipt.
 */
class InventoryCostLayer
{
    private ?int $id;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly ?int $batchId,
        private readonly int $locationId,
        private readonly string $valuationMethod,
        private readonly string $layerDate,
        private readonly string $quantityIn,
        private string $quantityRemaining,
        private string $unitCost,
        private readonly ?string $referenceType,
        private readonly ?int $referenceId,
        private bool $isClosed,
        ?int $id = null,
    ) {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getValuationMethod(): string
    {
        return $this->valuationMethod;
    }

    public function getLayerDate(): string
    {
        return $this->layerDate;
    }

    public function getQuantityIn(): string
    {
        return $this->quantityIn;
    }

    public function getQuantityRemaining(): string
    {
        return $this->quantityRemaining;
    }

    public function setQuantityRemaining(string $qty): void
    {
        $this->quantityRemaining = $qty;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    public function setUnitCost(string $unitCost): void
    {
        $this->unitCost = $unitCost;
    }

    public function getReferenceType(): ?string
    {
        return $this->referenceType;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function markClosed(): void
    {
        $this->isClosed = true;
    }

    /**
     * Deduct the given quantity from this layer.
     *
     * Closes the layer automatically when remaining reaches zero.
     */
    public function deduct(string $qty): void
    {
        $remaining = bcsub($this->quantityRemaining, $qty, 6);
        $this->quantityRemaining = $remaining;

        if (bccomp($remaining, '0.000000', 6) <= 0) {
            $this->quantityRemaining = '0.000000';
            $this->isClosed = true;
        }
    }
}
