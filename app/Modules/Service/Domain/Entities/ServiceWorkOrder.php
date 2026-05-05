<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

class ServiceWorkOrder
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $assetId,
        private readonly string $serviceType,
        private readonly int $currencyId,
        private readonly string $billingMode,
        private ?string $status = 'draft',
        private readonly ?int $orgUnitId = null,
        private readonly ?string $jobCardNumber = null,
        private readonly ?int $customerId = null,
        private readonly ?int $openedBy = null,
        private readonly ?int $assignedTeamOrgUnitId = null,
        private readonly string $priority = 'normal',
        private readonly ?string $openedAt = null,
        private readonly ?string $scheduledStartAt = null,
        private readonly ?string $scheduledEndAt = null,
        private readonly ?string $startedAt = null,
        private readonly ?string $completedAt = null,
        private readonly ?float $meterIn = null,
        private readonly ?float $meterOut = null,
        private readonly string $meterUnit = 'km',
        private readonly ?string $symptoms = null,
        private readonly ?string $diagnosis = null,
        private readonly ?string $resolution = null,
        private readonly float $laborSubtotal = 0.0,
        private readonly float $partsSubtotal = 0.0,
        private readonly float $otherSubtotal = 0.0,
        private readonly float $taxTotal = 0.0,
        private readonly float $grandTotal = 0.0,
        private readonly ?int $journalEntryId = null,
        private readonly ?string $notes = null,
        private readonly ?array $metadata = null,
        private readonly int $rowVersion = 1,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertServiceType($serviceType);
        $this->assertPriority($priority);
        $this->assertBillingMode($billingMode);
        $this->assertStatus($status ?? 'draft');
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }

    public function getOrgUnitId(): ?int { return $this->orgUnitId; }

    public function getJobCardNumber(): ?string { return $this->jobCardNumber; }

    public function getAssetId(): int { return $this->assetId; }

    public function getCustomerId(): ?int { return $this->customerId; }

    public function getOpenedBy(): ?int { return $this->openedBy; }

    public function getAssignedTeamOrgUnitId(): ?int { return $this->assignedTeamOrgUnitId; }

    public function getServiceType(): string { return $this->serviceType; }

    public function getPriority(): string { return $this->priority; }

    public function getStatus(): string { return $this->status ?? 'draft'; }

    public function getOpenedAt(): ?string { return $this->openedAt; }

    public function getScheduledStartAt(): ?string { return $this->scheduledStartAt; }

    public function getScheduledEndAt(): ?string { return $this->scheduledEndAt; }

    public function getStartedAt(): ?string { return $this->startedAt; }

    public function getCompletedAt(): ?string { return $this->completedAt; }

    public function getMeterIn(): ?float { return $this->meterIn; }

    public function getMeterOut(): ?float { return $this->meterOut; }

    public function getMeterUnit(): string { return $this->meterUnit; }

    public function getSymptoms(): ?string { return $this->symptoms; }

    public function getDiagnosis(): ?string { return $this->diagnosis; }

    public function getResolution(): ?string { return $this->resolution; }

    public function getBillingMode(): string { return $this->billingMode; }

    public function getCurrencyId(): int { return $this->currencyId; }

    public function getLaborSubtotal(): float { return $this->laborSubtotal; }

    public function getPartsSubtotal(): float { return $this->partsSubtotal; }

    public function getOtherSubtotal(): float { return $this->otherSubtotal; }

    public function getTaxTotal(): float { return $this->taxTotal; }

    public function getGrandTotal(): float { return $this->grandTotal; }

    public function getJournalEntryId(): ?int { return $this->journalEntryId; }

    public function getNotes(): ?string { return $this->notes; }

    public function getMetadata(): ?array { return $this->metadata; }

    public function getRowVersion(): int { return $this->rowVersion; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isTransitionAllowed(string $targetStatus): bool
    {
        $allowed = [
            'draft'       => ['open', 'cancelled'],
            'open'        => ['in_progress', 'on_hold', 'cancelled'],
            'in_progress' => ['on_hold', 'completed', 'cancelled'],
            'on_hold'     => ['in_progress', 'cancelled'],
            'completed'   => [],
            'cancelled'   => [],
        ];

        return in_array($targetStatus, $allowed[$this->getStatus()] ?? [], true);
    }

    public function triggersDowntime(): bool
    {
        return in_array($this->getStatus(), ['open', 'in_progress', 'on_hold'], true);
    }

    private function assertServiceType(string $type): void
    {
        if (! in_array($type, ['preventive', 'corrective', 'inspection', 'warranty', 'internal'], true)) {
            throw new \InvalidArgumentException("Invalid service type: {$type}");
        }
    }

    private function assertPriority(string $priority): void
    {
        if (! in_array($priority, ['low', 'normal', 'high', 'critical'], true)) {
            throw new \InvalidArgumentException("Invalid priority: {$priority}");
        }
    }

    private function assertBillingMode(string $mode): void
    {
        if (! in_array($mode, ['customer_billable', 'warranty', 'internal_cost', 'rental_intercompany'], true)) {
            throw new \InvalidArgumentException("Invalid billing mode: {$mode}");
        }
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['draft', 'open', 'in_progress', 'on_hold', 'completed', 'cancelled'], true)) {
            throw new \InvalidArgumentException("Invalid work order status: {$status}");
        }
    }
}
