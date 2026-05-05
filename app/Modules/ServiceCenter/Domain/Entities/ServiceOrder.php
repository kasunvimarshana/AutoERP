<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Domain\Entities;

final class ServiceOrder
{
    private string $id;
    private string $tenantId;
    private string $assetId;
    private ?string $assignedTechnicianId;
    private string $orderNumber;
    private string $serviceType;
    private string $status;
    private ?string $description;
    private ?\DateTime $scheduledAt;
    private ?\DateTime $startedAt;
    private ?\DateTime $completedAt;
    private string $estimatedCost;
    private string $totalCost;
    private int $version;

    public function __construct(
        string $id,
        string $tenantId,
        string $assetId,
        ?string $assignedTechnicianId,
        string $orderNumber,
        string $serviceType,
        string $status,
        ?string $description,
        ?\DateTime $scheduledAt,
        ?\DateTime $startedAt,
        ?\DateTime $completedAt,
        string $estimatedCost,
        string $totalCost,
        int $version = 1,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->assetId = $assetId;
        $this->assignedTechnicianId = $assignedTechnicianId;
        $this->orderNumber = $orderNumber;
        $this->serviceType = $serviceType;
        $this->status = $status;
        $this->description = $description;
        $this->scheduledAt = $scheduledAt;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
        $this->estimatedCost = $estimatedCost;
        $this->totalCost = $totalCost;
        $this->version = $version;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getAssetId(): string { return $this->assetId; }
    public function getAssignedTechnicianId(): ?string { return $this->assignedTechnicianId; }
    public function getOrderNumber(): string { return $this->orderNumber; }
    public function getServiceType(): string { return $this->serviceType; }
    public function getStatus(): string { return $this->status; }
    public function getDescription(): ?string { return $this->description; }
    public function getScheduledAt(): ?\DateTime { return $this->scheduledAt; }
    public function getStartedAt(): ?\DateTime { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTime { return $this->completedAt; }
    public function getEstimatedCost(): string { return $this->estimatedCost; }
    public function getTotalCost(): string { return $this->totalCost; }
    public function getVersion(): int { return $this->version; }

    public function start(\DateTime $startedAt): void
    {
        $this->status = 'in_progress';
        $this->startedAt = $startedAt;
        $this->version++;
    }

    public function complete(\DateTime $completedAt, string $totalCost): void
    {
        $this->status = 'completed';
        $this->completedAt = $completedAt;
        $this->totalCost = $totalCost;
        $this->version++;
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->version++;
    }

    public function assignTechnician(string $technicianId): void
    {
        $this->assignedTechnicianId = $technicianId;
        $this->version++;
    }
}
