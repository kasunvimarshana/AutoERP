<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class InventoryCycleCount
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private int $warehouseId;
    private ?int $zoneId;
    private ?int $locationId;
    private string $countMethod;
    private string $status;
    private ?int $assignedTo;
    private ?\DateTimeInterface $scheduledAt;
    private ?\DateTimeInterface $startedAt;
    private ?\DateTimeInterface $completedAt;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        int $warehouseId,
        ?int $zoneId = null,
        ?int $locationId = null,
        string $countMethod = 'manual',
        string $status = 'draft',
        ?int $assignedTo = null,
        ?\DateTimeInterface $scheduledAt = null,
        ?\DateTimeInterface $startedAt = null,
        ?\DateTimeInterface $completedAt = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->referenceNumber = $referenceNumber;
        $this->warehouseId     = $warehouseId;
        $this->zoneId          = $zoneId;
        $this->locationId      = $locationId;
        $this->countMethod     = $countMethod;
        $this->status          = $status;
        $this->assignedTo      = $assignedTo;
        $this->scheduledAt     = $scheduledAt;
        $this->startedAt       = $startedAt;
        $this->completedAt     = $completedAt;
        $this->notes           = $notes;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getZoneId(): ?int { return $this->zoneId; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getCountMethod(): string { return $this->countMethod; }
    public function getStatus(): string { return $this->status; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function getStartedAt(): ?\DateTimeInterface { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        string $referenceNumber,
        int $warehouseId,
        ?int $zoneId,
        ?int $locationId,
        string $countMethod,
        ?int $assignedTo,
        ?\DateTimeInterface $scheduledAt,
        ?string $notes,
        ?Metadata $metadata
    ): void {
        $this->referenceNumber = $referenceNumber;
        $this->warehouseId     = $warehouseId;
        $this->zoneId          = $zoneId;
        $this->locationId      = $locationId;
        $this->countMethod     = $countMethod;
        $this->assignedTo      = $assignedTo;
        $this->scheduledAt     = $scheduledAt;
        $this->notes           = $notes;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->updatedAt       = new \DateTimeImmutable;
    }

    public function start(): void
    {
        $this->status    = 'in_progress';
        $this->startedAt = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function complete(): void
    {
        $this->status      = 'completed';
        $this->completedAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }
}
