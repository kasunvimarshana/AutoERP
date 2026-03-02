<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Domain\Entities;

use DateTimeImmutable;
use Modules\Manufacturing\Domain\Enums\ProductionStatus;

/**
 * Production Order: an instruction to manufacture a quantity of a finished product.
 */
class ProductionOrder
{
    public function __construct(
        private readonly int              $id,
        private readonly int              $tenantId,
        private readonly string           $referenceNo,
        private readonly int              $productId,
        private readonly ?int             $variantId,
        private readonly int              $warehouseId,
        private readonly int              $bomId,
        private readonly string           $plannedQuantity,
        private readonly string           $producedQuantity,
        private readonly string           $totalCost,
        private readonly string           $wastagePercent,
        private readonly ProductionStatus $status,
        private readonly ?string          $notes,
        private readonly int              $createdBy,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public function getId(): int                      { return $this->id; }
    public function getTenantId(): int                { return $this->tenantId; }
    public function getReferenceNo(): string          { return $this->referenceNo; }
    public function getProductId(): int               { return $this->productId; }
    public function getVariantId(): ?int              { return $this->variantId; }
    public function getWarehouseId(): int             { return $this->warehouseId; }
    public function getBomId(): int                   { return $this->bomId; }
    public function getPlannedQuantity(): string      { return $this->plannedQuantity; }
    public function getProducedQuantity(): string     { return $this->producedQuantity; }
    public function getTotalCost(): string            { return $this->totalCost; }
    public function getWastagePercent(): string       { return $this->wastagePercent; }
    public function getStatus(): ProductionStatus     { return $this->status; }
    public function getNotes(): ?string               { return $this->notes; }
    public function getCreatedBy(): int               { return $this->createdBy; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }
}
