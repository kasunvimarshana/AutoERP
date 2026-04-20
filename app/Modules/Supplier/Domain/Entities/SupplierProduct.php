<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Entities;

class SupplierProduct
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private int $productId;

    private ?int $variantId;

    private ?string $supplierSku;

    private ?int $leadTimeDays;

    private string $minOrderQty;

    private bool $isPreferred;

    private ?string $lastPurchasePrice;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        int $productId,
        ?int $variantId = null,
        ?string $supplierSku = null,
        ?int $leadTimeDays = null,
        string $minOrderQty = '1.000000',
        bool $isPreferred = false,
        ?string $lastPurchasePrice = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertLeadTimeDays($leadTimeDays);
        $this->assertNumericNonNegative($minOrderQty, 'Minimum order quantity');
        if ((float) $minOrderQty <= 0.0) {
            throw new \InvalidArgumentException('Minimum order quantity must be greater than zero.');
        }

        if ($lastPurchasePrice !== null) {
            $this->assertNumericNonNegative($lastPurchasePrice, 'Last purchase price');
        }

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->supplierSku = $supplierSku;
        $this->leadTimeDays = $leadTimeDays;
        $this->minOrderQty = $this->normalizeDecimal($minOrderQty);
        $this->isPreferred = $isPreferred;
        $this->lastPurchasePrice = $lastPurchasePrice !== null ? $this->normalizeDecimal($lastPurchasePrice) : null;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getSupplierSku(): ?string
    {
        return $this->supplierSku;
    }

    public function getLeadTimeDays(): ?int
    {
        return $this->leadTimeDays;
    }

    public function getMinOrderQty(): string
    {
        return $this->minOrderQty;
    }

    public function isPreferred(): bool
    {
        return $this->isPreferred;
    }

    public function getLastPurchasePrice(): ?string
    {
        return $this->lastPurchasePrice;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        int $productId,
        ?int $variantId,
        ?string $supplierSku,
        ?int $leadTimeDays,
        string $minOrderQty,
        bool $isPreferred,
        ?string $lastPurchasePrice,
    ): void {
        $this->assertLeadTimeDays($leadTimeDays);
        $this->assertNumericNonNegative($minOrderQty, 'Minimum order quantity');
        if ((float) $minOrderQty <= 0.0) {
            throw new \InvalidArgumentException('Minimum order quantity must be greater than zero.');
        }

        if ($lastPurchasePrice !== null) {
            $this->assertNumericNonNegative($lastPurchasePrice, 'Last purchase price');
        }

        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->supplierSku = $supplierSku;
        $this->leadTimeDays = $leadTimeDays;
        $this->minOrderQty = $this->normalizeDecimal($minOrderQty);
        $this->isPreferred = $isPreferred;
        $this->lastPurchasePrice = $lastPurchasePrice !== null ? $this->normalizeDecimal($lastPurchasePrice) : null;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertLeadTimeDays(?int $leadTimeDays): void
    {
        if ($leadTimeDays !== null && $leadTimeDays < 0) {
            throw new \InvalidArgumentException('Lead time days cannot be negative.');
        }
    }

    private function assertNumericNonNegative(string $value, string $label): void
    {
        if (! is_numeric($value)) {
            throw new \InvalidArgumentException($label.' must be numeric.');
        }

        if ((float) $value < 0.0) {
            throw new \InvalidArgumentException($label.' cannot be negative.');
        }
    }

    private function normalizeDecimal(string $value): string
    {
        return number_format((float) $value, 6, '.', '');
    }
}
