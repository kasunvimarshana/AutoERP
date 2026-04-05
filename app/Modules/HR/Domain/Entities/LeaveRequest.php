<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\Exceptions\InvalidLeaveRequestException;

class LeaveRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $employeeId,
        private int $leaveTypeId,
        private \DateTimeInterface $startDate,
        private \DateTimeInterface $endDate,
        private float $totalDays,
        private string $status,
        private ?string $reason,
        private ?int $approvedById,
        private ?\DateTimeInterface $approvedAt,
        private ?string $rejectionReason,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getEmployeeId(): int { return $this->employeeId; }
    public function getLeaveTypeId(): int { return $this->leaveTypeId; }
    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function getEndDate(): \DateTimeInterface { return $this->endDate; }
    public function getTotalDays(): float { return $this->totalDays; }
    public function getStatus(): string { return $this->status; }
    public function getReason(): ?string { return $this->reason; }
    public function getApprovedById(): ?int { return $this->approvedById; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isPending(): bool { return $this->status === self::STATUS_PENDING; }
    public function isApproved(): bool { return $this->status === self::STATUS_APPROVED; }
    public function isRejected(): bool { return $this->status === self::STATUS_REJECTED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    public function approve(int $approverId): void
    {
        if (!$this->isPending()) {
            throw new InvalidLeaveRequestException('Only pending leave requests can be approved.');
        }
        $this->status = self::STATUS_APPROVED;
        $this->approvedById = $approverId;
        $this->approvedAt = new \DateTime();
    }

    public function reject(int $approverId, string $reason): void
    {
        if (!$this->isPending()) {
            throw new InvalidLeaveRequestException('Only pending leave requests can be rejected.');
        }
        $this->status = self::STATUS_REJECTED;
        $this->approvedById = $approverId;
        $this->approvedAt = new \DateTime();
        $this->rejectionReason = $reason;
    }

    public function cancel(): void
    {
        if ($this->isApproved() || $this->isRejected()) {
            throw new InvalidLeaveRequestException('Approved or rejected leave requests cannot be cancelled.');
        }
        $this->status = self::STATUS_CANCELLED;
    }
}
