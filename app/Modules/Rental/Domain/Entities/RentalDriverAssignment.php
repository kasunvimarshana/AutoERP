<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class RentalDriverAssignment
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $rentalBookingId,
        private readonly int $employeeId,
        private string $assignmentStatus = 'assigned',
        private readonly ?int $orgUnitId = null,
        private readonly ?int $substituteForAssignmentId = null,
        private readonly ?string $assignedFrom = null,
        private readonly ?string $assignedTo = null,
        private readonly ?string $substitutionReason = null,
        private readonly ?int $assignedBy = null,
        private readonly ?array $metadata = null,
        private readonly int $rowVersion = 1,
        private readonly ?int $id = null,
        private readonly ?\DateTimeInterface $createdAt = null,
        private readonly ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertAssignmentStatus($assignmentStatus);
    }

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }

    public function getOrgUnitId(): ?int { return $this->orgUnitId; }

    public function getRentalBookingId(): int { return $this->rentalBookingId; }

    public function getEmployeeId(): int { return $this->employeeId; }

    public function getSubstituteForAssignmentId(): ?int { return $this->substituteForAssignmentId; }

    public function getAssignmentStatus(): string { return $this->assignmentStatus; }

    public function getAssignedFrom(): ?string { return $this->assignedFrom; }

    public function getAssignedTo(): ?string { return $this->assignedTo; }

    public function getSubstitutionReason(): ?string { return $this->substitutionReason; }

    public function getAssignedBy(): ?int { return $this->assignedBy; }

    public function getMetadata(): ?array { return $this->metadata; }

    public function getRowVersion(): int { return $this->rowVersion; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isActive(): bool
    {
        return $this->assignmentStatus === 'assigned';
    }

    private function assertAssignmentStatus(string $status): void
    {
        if (! in_array($status, ['assigned', 'replaced', 'cancelled', 'completed'], true)) {
            throw new \InvalidArgumentException("Invalid assignment status: {$status}");
        }
    }
}
