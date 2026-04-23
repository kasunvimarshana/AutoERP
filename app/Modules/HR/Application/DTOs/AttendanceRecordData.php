<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class AttendanceRecordData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly string $attendanceDate,
        public readonly ?string $checkIn = null,
        public readonly ?string $checkOut = null,
        public readonly int $breakDuration = 0,
        public readonly string $status = 'present',
        public readonly ?int $shiftId = null,
        public readonly string $remarks = '',
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            attendanceDate: (string) $data['attendance_date'],
            checkIn: isset($data['check_in']) ? (string) $data['check_in'] : null,
            checkOut: isset($data['check_out']) ? (string) $data['check_out'] : null,
            breakDuration: isset($data['break_duration']) ? (int) $data['break_duration'] : 0,
            status: isset($data['status']) ? (string) $data['status'] : 'present',
            shiftId: isset($data['shift_id']) ? (int) $data['shift_id'] : null,
            remarks: isset($data['remarks']) ? (string) $data['remarks'] : '',
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'attendance_date' => $this->attendanceDate,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'break_duration' => $this->breakDuration,
            'status' => $this->status,
            'shift_id' => $this->shiftId,
            'remarks' => $this->remarks,
            'metadata' => $this->metadata,
        ];
    }
}
