<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Modules\Product\Domain\Enums\ProductType;
use Modules\Product\Domain\ValueObjects\SKU;

class Product
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private string $name,
        private readonly SKU $sku,
        private ?int $categoryId,
        private ?int $brandId,
        private ?int $unitId,
        private ProductType $type,
        private string $costPrice,
        private string $sellingPrice,
        private string $reorderPoint,
        private bool $isActive,
        private ?string $description,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSku(): SKU
    {
        return $this->sku;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function getUnitId(): ?int
    {
        return $this->unitId;
    }

    public function getType(): ProductType
    {
        return $this->type;
    }

    public function getCostPrice(): string
    {
        return $this->costPrice;
    }

    public function getSellingPrice(): string
    {
        return $this->sellingPrice;
    }

    public function getReorderPoint(): string
    {
        return $this->reorderPoint;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Calculates gross margin percentage using BCMath.
     * Formula: (sellingPrice - costPrice) / sellingPrice * 100
     */
    public function calculateMargin(): string
    {
        if (bccomp($this->sellingPrice, '0', 4) === 0) {
            return '0.0000';
        }

        $diff   = bcsub($this->sellingPrice, $this->costPrice, 4);
        $margin = bcdiv($diff, $this->sellingPrice, 8);

        return bcmul($margin, '100', 4);
    }

    public function updatePricing(string $costPrice, string $sellingPrice): void
    {
        if (bccomp($costPrice, '0', 4) < 0) {
            throw new \DomainException('Cost price cannot be negative.');
        }

        if (bccomp($sellingPrice, '0', 4) < 0) {
            throw new \DomainException('Selling price cannot be negative.');
        }

        $this->costPrice    = bcadd($costPrice, '0', 4);
        $this->sellingPrice = bcadd($sellingPrice, '0', 4);
    }
}
