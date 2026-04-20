<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class PurchaseInvoiceLine
{
    private ?int $id;

    private int $tenantId;

    private int $purchaseInvoiceId;

    private ?int $grnLineId;

    private int $productId;

    private ?int $variantId;

    private ?string $description;

    private int $uomId;

    private string $quantity;

    private string $unitPrice;

    private string $discountPct;

    private ?int $taxGroupId;

    private string $taxAmount;

    private string $lineTotal;

    private ?int $accountId;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $purchaseInvoiceId,
        int $productId,
        int $uomId,
        string $quantity,
        string $unitPrice,
        string $lineTotal,
        string $discountPct = '0',
        string $taxAmount = '0',
        ?int $grnLineId = null,
        ?int $variantId = null,
        ?string $description = null,
        ?int $taxGroupId = null,
        ?int $accountId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->purchaseInvoiceId = $purchaseInvoiceId;
        $this->productId = $productId;
        $this->uomId = $uomId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
        $this->discountPct = $discountPct;
        $this->taxAmount = $taxAmount;
        $this->grnLineId = $grnLineId;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->taxGroupId = $taxGroupId;
        $this->accountId = $accountId;
        $this->id = $id;
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

    public function getPurchaseInvoiceId(): int
    {
        return $this->purchaseInvoiceId;
    }

    public function getGrnLineId(): ?int
    {
        return $this->grnLineId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function getDiscountPct(): string
    {
        return $this->discountPct;
    }

    public function getTaxGroupId(): ?int
    {
        return $this->taxGroupId;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function getLineTotal(): string
    {
        return $this->lineTotal;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
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
        int $uomId,
        string $quantity,
        string $unitPrice,
        string $lineTotal,
        string $discountPct = '0',
        string $taxAmount = '0',
        ?int $grnLineId = null,
        ?int $variantId = null,
        ?string $description = null,
        ?int $taxGroupId = null,
        ?int $accountId = null,
    ): void {
        $this->productId = $productId;
        $this->uomId = $uomId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
        $this->discountPct = $discountPct;
        $this->taxAmount = $taxAmount;
        $this->grnLineId = $grnLineId;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->taxGroupId = $taxGroupId;
        $this->accountId = $accountId;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
