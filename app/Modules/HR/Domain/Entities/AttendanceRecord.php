<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\ValueObjects\AttendanceStatus;

class AttendanceRecord
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private \DateTimeInterface $attendanceDate,
        private ?\DateTimeInterface $checkIn,
        private ?\DateTimeInterface $checkOut,
        private int $breakDuration,
        private int $workedMinutes,
        private int $overtimeMinutes,
        private AttendanceStatus $status,
        private ?int $shiftId,
        private string $remarks,
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

    public function getAttendanceDate(): \DateTimeInterface
    {
        return $this->attendanceDate;
    }

    public function getCheckIn(): ?\DateTimeInterface
    {
        return $this->checkIn;
    }

    public function getCheckOut(): ?\DateTimeInterface
    {
        return $this->checkOut;
    }

    public function getBreakDuration(): int
    {
        return $this->breakDuration;
    }

    public function getWorkedMinutes(): int
    {
        return $this->workedMinutes;
    }

    public function getOvertimeMinutes(): int
    {
        return $this->overtimeMinutes;
    }

    public function getStatus(): AttendanceStatus
    {
        return $this->status;
    }

    public function getShiftId(): ?int
    {
        return $this->shiftId;
    }

    public function getRemarks(): string
    {
        return $this->remarks;
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

    public function calculateWorkedMinutes(): int
    {
        if ($this->checkIn === null || $this->checkOut === null) {
            return 0;
        }

        $diff = $this->checkOut->getTimestamp() - $this->checkIn->getTimestamp();
        $minutes = (int) ($diff / 60) - $this->breakDuration;

        return max(0, $minutes);
    }

    public function isLate(\DateTimeInterface $shiftStartTime, int $graceMinutes = 15): bool
    {
        if ($this->checkIn === null) {
            return false;
        }

        $diffSeconds = $this->checkIn->getTimestamp() - $shiftStartTime->getTimestamp();
        $diffMinutes = (int) ($diffSeconds / 60);

        return $diffMinutes > $graceMinutes;
    }
}
