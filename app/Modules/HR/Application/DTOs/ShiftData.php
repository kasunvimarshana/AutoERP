<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class ShiftData
{
    /**
     * @param  array<int, string>  $workDays
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $shiftType,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly int $breakDuration = 60,
        public readonly array $workDays = [],
        public readonly int $graceMinutes = 15,
        public readonly int $overtimeThreshold = 480,
        public readonly bool $isNightShift = false,
        public readonly array $metadata = [],
        public readonly bool $isActive = true,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            code: (string) $data['code'],
            shiftType: (string) $data['shift_type'],
            startTime: (string) $data['start_time'],
            endTime: (string) $data['end_time'],
            breakDuration: isset($data['break_duration']) ? (int) $data['break_duration'] : 60,
            workDays: isset($data['work_days']) && is_array($data['work_days']) ? $data['work_days'] : [],
            graceMinutes: isset($data['grace_minutes']) ? (int) $data['grace_minutes'] : 15,
            overtimeThreshold: isset($data['overtime_threshold']) ? (int) $data['overtime_threshold'] : 480,
            isNightShift: isset($data['is_night_shift']) ? (bool) $data['is_night_shift'] : false,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            rowVersion: (int) ($data['row_version'] ?? 1),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'code' => $this->code,
            'shift_type' => $this->shiftType,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'break_duration' => $this->breakDuration,
            'work_days' => $this->workDays,
            'grace_minutes' => $this->graceMinutes,
            'overtime_threshold' => $this->overtimeThreshold,
            'is_night_shift' => $this->isNightShift,
            'metadata' => $this->metadata,
            'is_active' => $this->isActive,
        ];
    }
}
