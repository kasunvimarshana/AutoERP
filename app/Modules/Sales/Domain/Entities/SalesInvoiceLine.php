<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class SalesInvoiceLine
{
    private ?int $id;

    private int $tenantId;

    private ?int $salesInvoiceId;

    private ?int $salesOrderLineId;

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

    private ?int $incomeAccountId;

    public function __construct(
        int $tenantId,
        int $productId,
        int $uomId,
        ?int $salesInvoiceId = null,
        ?int $salesOrderLineId = null,
        ?int $variantId = null,
        ?string $description = null,
        string $quantity = '0.000000',
        string $unitPrice = '0.000000',
        string $discountPct = '0.000000',
        ?int $taxGroupId = null,
        string $taxAmount = '0.000000',
        string $lineTotal = '0.000000',
        ?int $incomeAccountId = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->salesInvoiceId = $salesInvoiceId;
        $this->salesOrderLineId = $salesOrderLineId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->uomId = $uomId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->discountPct = $discountPct;
        $this->taxGroupId = $taxGroupId;
        $this->taxAmount = $taxAmount;
        $this->lineTotal = $lineTotal;
        $this->incomeAccountId = $incomeAccountId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSalesInvoiceId(): ?int
    {
        return $this->salesInvoiceId;
    }

    public function getSalesOrderLineId(): ?int
    {
        return $this->salesOrderLineId;
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

    public function getIncomeAccountId(): ?int
    {
        return $this->incomeAccountId;
    }

    public function update(
        int $productId,
        int $uomId,
        ?int $salesOrderLineId = null,
        ?int $variantId = null,
        ?string $description = null,
        string $quantity = '0.000000',
        string $unitPrice = '0.000000',
        string $discountPct = '0.000000',
        ?int $taxGroupId = null,
        string $taxAmount = '0.000000',
        string $lineTotal = '0.000000',
        ?int $incomeAccountId = null,
    ): void {
        $this->productId = $productId;
        $this->salesOrderLineId = $salesOrderLineId;
        $this->variantId = $variantId;
        $this->description = $description;
        $this->uomId = $uomId;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->discountPct = $discountPct;
        $this->taxGroupId = $taxGroupId;
        $this->taxAmount = $taxAmount;
        $this->lineTotal = $lineTotal;
        $this->incomeAccountId = $incomeAccountId;
    }
}
