<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class PurchaseOrderLine
{
    private ?int $id;

    private int $tenantId;
    private int $purchaseOrderId;

    private int $productId;

    private ?int $variantId;

    private ?string $description;

    private int $uomId;

    private string $orderedQty;

    private string $receivedQty;

    private string $unitPrice;

    private string $discountPct;

    private ?int $taxGroupId;

    private ?int $accountId;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;
    private string $lineTotal;

    public function __construct(
        int $tenantId,
        int $purchaseOrderId,
        int $productId,
        int $uomId,
        string $orderedQty,
        string $unitPrice,
        string $receivedQty = '0',
        string $discountPct = '0',
        ?int $variantId = null,
        ?string $description = null,
        ?int $taxGroupId = null,
        ?int $accountId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?string $lineTotal = null,
    ) {
        $this->tenantId = $tenantId;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->productId = $productId;
        $this->uomId = $uomId;
        $this->orderedQty = $orderedQty;
        $this->unitPrice = $unitPrice;
        $this->receivedQty = $receivedQty;
        $this->discountPct = $discountPct;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->taxGroupId = $taxGroupId;
        $this->accountId = $accountId;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
            // Use provided lineTotal or compute from orderedQty × unitPrice × (1 - discountPct/100)
            if ($lineTotal !== null) {
                $this->lineTotal = $lineTotal;
            } else {
                $gross = bcmul($orderedQty, $unitPrice, 6);
                $discountAmount = bcdiv(bcmul($gross, $discountPct, 6), '100', 6);
                $this->lineTotal = bcsub($gross, $discountAmount, 6);
            }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getPurchaseOrderId(): int
    {
        return $this->purchaseOrderId;
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

    public function getOrderedQty(): string
    {
        return $this->orderedQty;
    }

    public function getReceivedQty(): string
    {
        return $this->receivedQty;
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
        string $orderedQty,
        string $unitPrice,
        string $discountPct = '0',
        ?int $variantId = null,
        ?string $description = null,
        ?int $taxGroupId = null,
        ?int $accountId = null,
    ): void {
        $this->productId = $productId;
        $this->uomId = $uomId;
        $this->orderedQty = $orderedQty;
        $this->unitPrice = $unitPrice;
        $this->discountPct = $discountPct;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->taxGroupId = $taxGroupId;
        $this->accountId = $accountId;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function addReceivedQty(string $qty): void
    {
        $this->receivedQty = bcadd($this->receivedQty, $qty, 6);
        $this->updatedAt = new \DateTimeImmutable;
    }
}
    public function getLineTotal(): string
    {
        return $this->lineTotal;
    }

    public function getCreatedAt(): \DateTimeInterface
