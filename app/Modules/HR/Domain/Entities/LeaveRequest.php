<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;

class LeaveRequest
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private readonly int $leaveTypeId,
        private \DateTimeInterface $startDate,
        private \DateTimeInterface $endDate,
        private float $totalDays,
        private string $reason,
        private LeaveRequestStatus $status,
        private ?int $approverId,
        private string $approverNote,
        private ?string $attachmentPath,
        private array $metadata,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

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

    public function getLeaveTypeId(): int
    {
        return $this->leaveTypeId;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function getTotalDays(): float
    {
        return $this->totalDays;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getStatus(): LeaveRequestStatus
    {
        return $this->status;
    }

    public function getApproverId(): ?int
    {
        return $this->approverId;
    }

    public function getApproverNote(): string
    {
        return $this->approverNote;
    }

    public function getAttachmentPath(): ?string
    {
        return $this->attachmentPath;
    }

    public function getMetadata(): array
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

    public function approve(int $approverId, string $note = ''): void
    {
        $this->status = LeaveRequestStatus::APPROVED;
        $this->approverId = $approverId;
        $this->approverNote = $note;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function reject(int $approverId, string $reason): void
    {
        $this->status = LeaveRequestStatus::REJECTED;
        $this->approverId = $approverId;
        $this->approverNote = $reason;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status = LeaveRequestStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function recall(): void
    {
        $this->status = LeaveRequestStatus::RECALLED;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
