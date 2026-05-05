<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class StockReorderRule
{
    private ?int $id;

    public function __construct(
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly int $warehouseId,
        private readonly string $minimumQuantity,
        private readonly ?string $maximumQuantity,
        private readonly string $reorderQuantity,
        private bool $isActive,
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

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getMinimumQuantity(): string
    {
        return $this->minimumQuantity;
    }

    public function getMaximumQuantity(): ?string
    {
        return $this->maximumQuantity;
    }

    public function getReorderQuantity(): string
    {
        return $this->reorderQuantity;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }
}
