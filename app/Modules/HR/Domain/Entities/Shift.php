<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

use Modules\HR\Domain\ValueObjects\ShiftType;

class Shift
{
    public function __construct(
        private readonly int $tenantId,
        private string $name,
        private string $code,
        private ShiftType $shiftType,
        private string $startTime,
        private string $endTime,
        private int $breakDuration,
        private array $workDays,
        private int $graceMinutes,
        private int $overtimeThreshold,
        private bool $isNightShift,
        private array $metadata,
        private bool $isActive,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private int $rowVersion = 1,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getShiftType(): ShiftType
    {
        return $this->shiftType;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function getBreakDuration(): int
    {
        return $this->breakDuration;
    }

    public function getWorkDays(): array
    {
        return $this->workDays;
    }

    public function getGraceMinutes(): int
    {
        return $this->graceMinutes;
    }

    public function getOvertimeThreshold(): int
    {
        return $this->overtimeThreshold;
    }

    public function isNightShift(): bool
    {
        return $this->isNightShift;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }


    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function getWorkingMinutes(): int
    {
        [$startHour, $startMin] = array_map('intval', explode(':', $this->startTime));
        [$endHour, $endMin] = array_map('intval', explode(':', $this->endTime));

        $startTotalMinutes = $startHour * 60 + $startMin;
        $endTotalMinutes = $endHour * 60 + $endMin;

        if ($this->isNightShift && $endTotalMinutes <= $startTotalMinutes) {
            $endTotalMinutes += 24 * 60;
        }

        $working = $endTotalMinutes - $startTotalMinutes - $this->breakDuration;

        return max(0, $working);
    }
}
