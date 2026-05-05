<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

class ServiceMaintenancePlan
{
    private ?int $id;

    public function __construct(
        private int $tenantId,
        private string $planCode,
        private string $planName,
        private string $triggerType,
        private string $status = 'active',
        private ?int $orgUnitId = null,
        private ?int $assetId = null,
        private ?int $productId = null,
        private ?int $assignedEmployeeId = null,
        private ?string $description = null,
        private ?int $intervalDays = null,
        private ?string $intervalKm = null,
        private ?string $intervalHours = null,
        private ?int $advanceNoticeDays = null,
        private ?\DateTimeInterface $lastServicedAt = null,
        private ?\DateTimeInterface $nextServiceDueAt = null,
        private ?string $lastServiceOdometer = null,
        private ?string $nextServiceOdometer = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getPlanCode(): string { return $this->planCode; }
    public function getPlanName(): string { return $this->planName; }
    public function getDescription(): ?string { return $this->description; }
    public function getAssetId(): ?int { return $this->assetId; }
    public function getProductId(): ?int { return $this->productId; }
    public function getTriggerType(): string { return $this->triggerType; }
    public function getIntervalDays(): ?int { return $this->intervalDays; }
    public function getIntervalKm(): ?string { return $this->intervalKm; }
    public function getIntervalHours(): ?string { return $this->intervalHours; }
    public function getAdvanceNoticeDays(): ?int { return $this->advanceNoticeDays; }
    public function getLastServicedAt(): ?\DateTimeInterface { return $this->lastServicedAt; }
    public function getNextServiceDueAt(): ?\DateTimeInterface { return $this->nextServiceDueAt; }
    public function getLastServiceOdometer(): ?string { return $this->lastServiceOdometer; }
    public function getNextServiceOdometer(): ?string { return $this->nextServiceOdometer; }
    public function getAssignedEmployeeId(): ?int { return $this->assignedEmployeeId; }
    public function getStatus(): string { return $this->status; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function update(array $fields): void
    {
        foreach ($fields as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $this->rowVersion++;
    }
}
