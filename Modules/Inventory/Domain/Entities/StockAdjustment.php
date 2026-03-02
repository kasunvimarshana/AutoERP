<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeImmutable;

class StockAdjustment
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly int $warehouseId,
        private string $referenceNo,
        private string $reason,
        private string $totalAmount,
        private string $status,
        private readonly int $adjustedBy,
        private readonly ?DateTimeImmutable $adjustedAt,
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getReferenceNo(): string { return $this->referenceNo; }
    public function getReason(): string { return $this->reason; }
    public function getTotalAmount(): string { return $this->totalAmount; }
    public function getStatus(): string { return $this->status; }
    public function getAdjustedBy(): int { return $this->adjustedBy; }
    public function getAdjustedAt(): ?DateTimeImmutable { return $this->adjustedAt; }
}
