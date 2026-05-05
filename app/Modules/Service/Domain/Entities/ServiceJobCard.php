<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

class ServiceJobCard
{
    private ?int $id;

    public function __construct(
        private int $tenantId,
        private string $jobNumber,
        private string $serviceType,
        private string $priority,
        private string $status = 'open',
        private ?int $orgUnitId = null,
        private ?int $assetId = null,
        private ?int $customerId = null,
        private ?int $maintenancePlanId = null,
        private ?int $assignedTo = null,
        private ?int $arTransactionId = null,
        private ?int $journalEntryId = null,
        private ?\DateTimeInterface $scheduledAt = null,
        private ?\DateTimeInterface $startedAt = null,
        private ?\DateTimeInterface $completedAt = null,
        private ?string $odometerIn = null,
        private ?string $odometerOut = null,
        private bool $isBillable = true,
        private string $partsSubtotal = '0.000000',
        private string $labourSubtotal = '0.000000',
        private string $discountAmount = '0.000000',
        private string $taxAmount = '0.000000',
        private string $totalAmount = '0.000000',
        private ?string $diagnosis = null,
        private ?string $workPerformed = null,
        private ?string $notes = null,
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
    public function getJobNumber(): string { return $this->jobNumber; }
    public function getAssetId(): ?int { return $this->assetId; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getMaintenancePlanId(): ?int { return $this->maintenancePlanId; }
    public function getServiceType(): string { return $this->serviceType; }
    public function getPriority(): string { return $this->priority; }
    public function getStatus(): string { return $this->status; }
    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function getStartedAt(): ?\DateTimeInterface { return $this->startedAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function getOdometerIn(): ?string { return $this->odometerIn; }
    public function getOdometerOut(): ?string { return $this->odometerOut; }
    public function isBillable(): bool { return $this->isBillable; }
    public function getPartsSubtotal(): string { return $this->partsSubtotal; }
    public function getLabourSubtotal(): string { return $this->labourSubtotal; }
    public function getDiscountAmount(): string { return $this->discountAmount; }
    public function getTaxAmount(): string { return $this->taxAmount; }
    public function getTotalAmount(): string { return $this->totalAmount; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getArTransactionId(): ?int { return $this->arTransactionId; }
    public function getJournalEntryId(): ?int { return $this->journalEntryId; }
    public function getDiagnosis(): ?string { return $this->diagnosis; }
    public function getWorkPerformed(): ?string { return $this->workPerformed; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->completedAt = new \DateTimeImmutable;
        $this->rowVersion++;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->rowVersion++;
    }

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
