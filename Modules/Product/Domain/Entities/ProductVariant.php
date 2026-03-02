<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Modules\Product\Domain\ValueObjects\SKU;

class ProductVariant
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly int $productId,
        private string $name,
        private readonly SKU $sku,
        private string $costPrice,
        private string $sellingPrice,
        private array $attributes,
        private bool $isActive,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSku(): SKU
    {
        return $this->sku;
    }

    public function getCostPrice(): string
    {
        return $this->costPrice;
    }

    public function getSellingPrice(): string
    {
        return $this->sellingPrice;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
