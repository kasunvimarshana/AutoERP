<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class SalesReturnLine
{
    private ?int $id;

    private int $tenantId;

    private ?int $salesReturnId;

    private ?int $originalSalesOrderLineId;

    private int $productId;

    private ?int $variantId;

    private ?int $batchId;

    private ?int $serialId;

    private int $toLocationId;

    private int $uomId;

    private string $returnQty;

    private string $unitPrice;

    private string $lineTotal;

    private string $condition;

    private string $disposition;

    private string $restockingFee;

    private ?string $qualityCheckNotes;

    private const ALLOWED_CONDITIONS = ['good', 'damaged', 'expired', 'defective'];

    private const ALLOWED_DISPOSITIONS = ['restock', 'scrap', 'quarantine'];

    public function __construct(
        int $tenantId,
        int $productId,
        int $toLocationId,
        int $uomId,
        ?int $salesReturnId = null,
        ?int $originalSalesOrderLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        string $returnQty = '0.000000',
        string $unitPrice = '0.000000',
        string $lineTotal = '0.000000',
        string $condition = 'good',
        string $disposition = 'restock',
        string $restockingFee = '0.000000',
        ?string $qualityCheckNotes = null,
        ?int $id = null,
    ) {
        $this->assertCondition($condition);
        $this->assertDisposition($disposition);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->salesReturnId = $salesReturnId;
        $this->originalSalesOrderLineId = $originalSalesOrderLineId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->toLocationId = $toLocationId;
        $this->uomId = $uomId;
        $this->returnQty = $returnQty;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
        $this->condition = $condition;
        $this->disposition = $disposition;
        $this->restockingFee = $restockingFee;
        $this->qualityCheckNotes = $qualityCheckNotes;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSalesReturnId(): ?int
    {
        return $this->salesReturnId;
    }

    public function getOriginalSalesOrderLineId(): ?int
    {
        return $this->originalSalesOrderLineId;
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

    public function getSerialId(): ?int
    {
        return $this->serialId;
    }

    public function getToLocationId(): int
    {
        return $this->toLocationId;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getReturnQty(): string
    {
        return $this->returnQty;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function getLineTotal(): string
    {
        return $this->lineTotal;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function getDisposition(): string
    {
        return $this->disposition;
    }

    public function getRestockingFee(): string
    {
        return $this->restockingFee;
    }

    public function getQualityCheckNotes(): ?string
    {
        return $this->qualityCheckNotes;
    }

    public function update(
        int $productId,
        int $toLocationId,
        int $uomId,
        ?int $originalSalesOrderLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        string $returnQty = '0.000000',
        string $unitPrice = '0.000000',
        string $lineTotal = '0.000000',
        string $condition = 'good',
        string $disposition = 'restock',
        string $restockingFee = '0.000000',
        ?string $qualityCheckNotes = null,
    ): void {
        $this->assertCondition($condition);
        $this->assertDisposition($disposition);

        $this->productId = $productId;
        $this->originalSalesOrderLineId = $originalSalesOrderLineId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->toLocationId = $toLocationId;
        $this->uomId = $uomId;
        $this->returnQty = $returnQty;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
        $this->condition = $condition;
        $this->disposition = $disposition;
        $this->restockingFee = $restockingFee;
        $this->qualityCheckNotes = $qualityCheckNotes;
    }

    private function assertCondition(string $condition): void
    {
        if (! in_array($condition, self::ALLOWED_CONDITIONS, true)) {
            throw new \InvalidArgumentException(
                'Invalid condition. Allowed: '.implode(', ', self::ALLOWED_CONDITIONS)
            );
        }
    }

    private function assertDisposition(string $disposition): void
    {
        if (! in_array($disposition, self::ALLOWED_DISPOSITIONS, true)) {
            throw new \InvalidArgumentException(
                'Invalid disposition. Allowed: '.implode(', ', self::ALLOWED_DISPOSITIONS)
            );
        }
    }
}
