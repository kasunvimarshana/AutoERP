<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class CycleCountLine
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly ?int $batchId,
        private readonly ?int $serialId,
        private readonly string $systemQty,
        private readonly string $countedQty,
        private readonly string $varianceQty,
        private readonly string $unitCost,
        private readonly string $varianceValue,
        private readonly ?int $adjustmentMovementId,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
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

    public function getSystemQty(): string
    {
        return $this->systemQty;
    }

    public function getCountedQty(): string
    {
        return $this->countedQty;
    }

    public function getVarianceQty(): string
    {
        return $this->varianceQty;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    public function getVarianceValue(): string
    {
        return $this->varianceValue;
    }

    public function getAdjustmentMovementId(): ?int
    {
        return $this->adjustmentMovementId;
    }
}
