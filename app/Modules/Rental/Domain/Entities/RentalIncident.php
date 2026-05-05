<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class RentalIncident
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $rentalBookingId,
        private readonly int $assetId,
        private readonly string $incidentType,
        private string $status = 'open',
        private readonly ?int $orgUnitId = null,
        private readonly ?string $occurredAt = null,
        private readonly ?int $reportedBy = null,
        private readonly ?string $description = null,
        private readonly float $estimatedCost = 0.0,
        private readonly float $recoveredAmount = 0.0,
        private readonly string $recoveryStatus = 'none',
        private readonly ?array $metadata = null,
        private readonly int $rowVersion = 1,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertIncidentType($incidentType);
        $this->assertStatus($status);
        $this->assertRecoveryStatus($recoveryStatus);
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }

    public function getOrgUnitId(): ?int { return $this->orgUnitId; }

    public function getRentalBookingId(): int { return $this->rentalBookingId; }

    public function getAssetId(): int { return $this->assetId; }

    public function getIncidentType(): string { return $this->incidentType; }

    public function getStatus(): string { return $this->status; }

    public function getOccurredAt(): ?string { return $this->occurredAt; }

    public function getReportedBy(): ?int { return $this->reportedBy; }

    public function getDescription(): ?string { return $this->description; }

    public function getEstimatedCost(): float { return $this->estimatedCost; }

    public function getRecoveredAmount(): float { return $this->recoveredAmount; }

    public function getRecoveryStatus(): string { return $this->recoveryStatus; }

    public function getMetadata(): ?array { return $this->metadata; }

    public function getRowVersion(): int { return $this->rowVersion; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    private function assertIncidentType(string $type): void
    {
        if (! in_array($type, ['damage', 'traffic_violation', 'late_return', 'other'], true)) {
            throw new \InvalidArgumentException("Invalid incident type: {$type}");
        }
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, ['open', 'under_review', 'resolved', 'waived'], true)) {
            throw new \InvalidArgumentException("Invalid incident status: {$status}");
        }
    }

    private function assertRecoveryStatus(string $status): void
    {
        if (! in_array($status, ['none', 'partial', 'full'], true)) {
            throw new \InvalidArgumentException("Invalid recovery status: {$status}");
        }
    }
}
