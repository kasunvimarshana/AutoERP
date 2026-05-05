<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

class PriceListItem
{
    private ?int $id;

    private int $tenantId;

    private int $priceListId;

    private int $productId;

    private ?int $variantId;

    private int $uomId;

    private string $minQuantity;

    private string $price;

    private string $discountPct;

    private ?\DateTimeInterface $validFrom;

    private ?\DateTimeInterface $validTo;

    private \DateTimeInterface $createdAt;

    private int $rowVersion;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $priceListId,
        int $productId,
        int $uomId,
        string $price,
        string $minQuantity = '1.000000',
        ?int $variantId = null,
        string $discountPct = '0.000000',
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validTo = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertNumeric($minQuantity, 'Minimum quantity');
        $this->assertPositive($minQuantity, 'Minimum quantity');
        $this->assertNumeric($price, 'Price');
        $this->assertNonNegative($price, 'Price');
        $this->assertNumeric($discountPct, 'Discount percentage');
        $this->assertDiscountRange($discountPct);
        $this->assertDateRange($validFrom, $validTo);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->priceListId = $priceListId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->uomId = $uomId;
        $this->minQuantity = $this->normalizeDecimal($minQuantity);
        $this->price = $this->normalizeDecimal($price);
        $this->discountPct = $this->normalizeDecimal($discountPct);
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->rowVersion = $rowVersion;
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

    public function getPriceListId(): int
    {
        return $this->priceListId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getMinQuantity(): string
    {
        return $this->minQuantity;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getDiscountPct(): string
    {
        return $this->discountPct;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->validTo;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }


    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function update(
        int $productId,
        ?int $variantId,
        int $uomId,
        string $price,
        string $minQuantity,
        string $discountPct,
        ?\DateTimeInterface $validFrom,
        ?\DateTimeInterface $validTo,
    ): void {
        $this->assertNumeric($minQuantity, 'Minimum quantity');
        $this->assertPositive($minQuantity, 'Minimum quantity');
        $this->assertNumeric($price, 'Price');
        $this->assertNonNegative($price, 'Price');
        $this->assertNumeric($discountPct, 'Discount percentage');
        $this->assertDiscountRange($discountPct);
        $this->assertDateRange($validFrom, $validTo);

        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->uomId = $uomId;
        $this->price = $this->normalizeDecimal($price);
        $this->minQuantity = $this->normalizeDecimal($minQuantity);
        $this->discountPct = $this->normalizeDecimal($discountPct);
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertDateRange(?\DateTimeInterface $validFrom, ?\DateTimeInterface $validTo): void
    {
        if ($validFrom !== null && $validTo !== null && $validTo < $validFrom) {
            throw new \InvalidArgumentException('Price list item valid_to cannot be earlier than valid_from.');
        }
    }

    private function assertNumeric(string $value, string $label): void
    {
        if (! is_numeric($value)) {
            throw new \InvalidArgumentException($label.' must be numeric.');
        }
    }

    private function assertPositive(string $value, string $label): void
    {
        if ((float) $value <= 0.0) {
            throw new \InvalidArgumentException($label.' must be greater than zero.');
        }
    }

    private function assertNonNegative(string $value, string $label): void
    {
        if ((float) $value < 0.0) {
            throw new \InvalidArgumentException($label.' cannot be negative.');
        }
    }

    private function assertDiscountRange(string $discountPct): void
    {
        $value = (float) $discountPct;
        if ($value < 0.0 || $value > 100.0) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100.');
        }
    }

    private function normalizeDecimal(string $value): string
    {
        return number_format((float) $value, 6, '.', '');
    }
}
