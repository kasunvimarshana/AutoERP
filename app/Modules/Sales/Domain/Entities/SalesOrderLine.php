<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class SalesOrderLine
{
    private ?int $id;

    private int $tenantId;

    private ?int $salesOrderId;

    private int $productId;

    private ?int $variantId;

    private ?string $description;

    private int $uomId;

    private string $orderedQty;

    private string $shippedQty;

    private string $reservedQty;

    private string $unitPrice;

    private string $discountPct;

    private ?int $taxGroupId;

    private string $lineTotal;

    private ?int $incomeAccountId;

    private ?int $batchId;

    private ?int $serialId;

    public function __construct(
        int $tenantId,
        int $productId,
        int $uomId,
        ?int $salesOrderId = null,
        ?int $variantId = null,
        ?string $description = null,
        string $orderedQty = '0.000000',
        string $shippedQty = '0.000000',
        string $reservedQty = '0.000000',
        string $unitPrice = '0.000000',
        string $discountPct = '0.000000',
        ?int $taxGroupId = null,
        string $lineTotal = '0.000000',
        ?int $incomeAccountId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->salesOrderId = $salesOrderId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->uomId = $uomId;
        $this->orderedQty = $orderedQty;
        $this->shippedQty = $shippedQty;
        $this->reservedQty = $reservedQty;
        $this->unitPrice = $unitPrice;
        $this->discountPct = $discountPct;
        $this->taxGroupId = $taxGroupId;
        $this->lineTotal = $lineTotal;
        $this->incomeAccountId = $incomeAccountId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSalesOrderId(): ?int
    {
        return $this->salesOrderId;
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

    public function getShippedQty(): string
    {
        return $this->shippedQty;
    }

    public function getReservedQty(): string
    {
        return $this->reservedQty;
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

    public function getLineTotal(): string
    {
        return $this->lineTotal;
    }

    public function getIncomeAccountId(): ?int
    {
        return $this->incomeAccountId;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    public function getSerialId(): ?int
    {
        return $this->serialId;
    }

    public function update(
        int $productId,
        int $uomId,
        ?int $variantId = null,
        ?string $description = null,
        string $orderedQty = '0.000000',
        string $shippedQty = '0.000000',
        string $reservedQty = '0.000000',
        string $unitPrice = '0.000000',
        string $discountPct = '0.000000',
        ?int $taxGroupId = null,
        string $lineTotal = '0.000000',
        ?int $incomeAccountId = null,
        ?int $batchId = null,
        ?int $serialId = null,
    ): void {
        $this->productId = $productId;
        $this->uomId = $uomId;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->orderedQty = $orderedQty;
        $this->shippedQty = $shippedQty;
        $this->reservedQty = $reservedQty;
        $this->unitPrice = $unitPrice;
        $this->discountPct = $discountPct;
        $this->taxGroupId = $taxGroupId;
        $this->lineTotal = $lineTotal;
        $this->incomeAccountId = $incomeAccountId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
    }
}
