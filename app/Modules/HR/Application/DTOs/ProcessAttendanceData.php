<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class ProcessAttendanceData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly string $attendanceDate,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            attendanceDate: (string) $data['attendance_date'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'attendance_date' => $this->attendanceDate,
        ];
    }
}
