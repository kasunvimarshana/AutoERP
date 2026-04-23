<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class LeaveBalanceData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly int $leaveTypeId,
        public readonly int $year,
        public readonly float $allocated = 0.0,
        public readonly float $used = 0.0,
        public readonly float $pending = 0.0,
        public readonly float $carried = 0.0,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            leaveTypeId: (int) $data['leave_type_id'],
            year: (int) $data['year'],
            allocated: isset($data['allocated']) ? (float) $data['allocated'] : 0.0,
            used: isset($data['used']) ? (float) $data['used'] : 0.0,
            pending: isset($data['pending']) ? (float) $data['pending'] : 0.0,
            carried: isset($data['carried']) ? (float) $data['carried'] : 0.0,
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
            'leave_type_id' => $this->leaveTypeId,
            'year' => $this->year,
            'allocated' => $this->allocated,
            'used' => $this->used,
            'pending' => $this->pending,
            'carried' => $this->carried,
        ];
    }
}
