<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use DateTimeImmutable;

class Attendance
{
    private ?DateTimeImmutable $checkOutTime = null;

    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        private int $tenantId,
        private int $employeeId,
        private string $date,
        private DateTimeImmutable $checkInTime,
        private string $status,
        private ?string $notes = null,
        private ?float $hoursWorked = null,
        private ?int $id = null,
        private ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->updatedAt = $updatedAt;
    }

    public function checkOut(DateTimeImmutable $checkOutTime, ?float $hoursWorked = null): void
    {
        $this->checkOutTime = $checkOutTime;
        if ($hoursWorked !== null) {
            $this->hoursWorked = $hoursWorked;
        }
        $this->updatedAt = new DateTimeImmutable;
    }

    public function checkIn(DateTimeImmutable $checkInTime): void
    {
        $this->checkInTime = $checkInTime;
        $this->updatedAt   = new DateTimeImmutable;
    }

    public function updateDetails(
        string $date,
        DateTimeImmutable $checkInTime,
        string $status,
        ?string $notes = null,
        ?float $hoursWorked = null,
        ?DateTimeImmutable $checkOutTime = null,
    ): void {
        $this->date         = $date;
        $this->checkInTime  = $checkInTime;
        $this->status       = $status;
        $this->notes        = $notes;
        $this->hoursWorked  = $hoursWorked;
        $this->checkOutTime = $checkOutTime;
        $this->updatedAt    = new DateTimeImmutable;
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

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCheckInTime(): DateTimeImmutable
    {
        return $this->checkInTime;
    }

    public function getCheckOutTime(): ?DateTimeImmutable
    {
        return $this->checkOutTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getHoursWorked(): ?float
    {
        return $this->hoursWorked;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
