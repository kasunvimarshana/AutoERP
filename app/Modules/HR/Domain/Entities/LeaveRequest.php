<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class LeaveRequest
{
    private ?int $id;

    private int $tenantId;

    private int $employeeId;

    private string $leaveType;

    private \DateTimeInterface $startDate;

    private \DateTimeInterface $endDate;

    private ?string $reason;

    private string $status;

    private ?int $approvedBy;

    private ?\DateTimeInterface $approvedAt;

    private ?string $notes;

    private Metadata $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $employeeId,
        string $leaveType,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?string $reason = null,
        string $status = 'pending',
        ?int $approvedBy = null,
        ?\DateTimeInterface $approvedAt = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id         = $id;
        $this->tenantId   = $tenantId;
        $this->employeeId = $employeeId;
        $this->leaveType  = $leaveType;
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->reason     = $reason;
        $this->status     = $status;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = $approvedAt;
        $this->notes      = $notes;
        $this->metadata   = $metadata ?? new Metadata([]);
        $this->createdAt  = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt  = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getLeaveType(): string
    {
        return $this->leaveType;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getApprovedBy(): ?int
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function approve(int $approvedBy, ?string $notes): void
    {
        $this->status     = 'approved';
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new \DateTimeImmutable;
        $this->notes      = $notes;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function reject(int $approvedBy, ?string $notes): void
    {
        $this->status     = 'rejected';
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new \DateTimeImmutable;
        $this->notes      = $notes;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
