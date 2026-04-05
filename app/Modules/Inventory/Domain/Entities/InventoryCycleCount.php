<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryCycleCount
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $warehouseId,
        private ?int $productId,
        private string $status,   // pending|in_progress|completed|cancelled
        private ?int $countedBy,
        private ?\DateTimeInterface $startedAt,
        private ?\DateTimeInterface $completedAt,
        private ?string $notes,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getProductId(): ?int { return $this->productId; }
    public function getStatus(): string { return $this->status; }
    public function getCountedBy(): ?int { return $this->countedBy; }
    public function getStartedAt(): ?\DateTimeInterface { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function start(int $countedBy): void
    {
        if ($this->status !== 'pending') throw new \DomainException("Can only start a pending cycle count.");
        $this->status    = 'in_progress';
        $this->countedBy = $countedBy;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function complete(): void
    {
        if ($this->status !== 'in_progress') throw new \DomainException("Can only complete an in-progress cycle count.");
        $this->status      = 'completed';
        $this->completedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === 'completed') throw new \DomainException("Cannot cancel a completed cycle count.");
        $this->status = 'cancelled';
    }
}
