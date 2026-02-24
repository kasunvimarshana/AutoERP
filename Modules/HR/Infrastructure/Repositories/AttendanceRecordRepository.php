<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\AttendanceRecordRepositoryInterface;
use Modules\HR\Infrastructure\Models\AttendanceRecordModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class AttendanceRecordRepository extends BaseEloquentRepository implements AttendanceRecordRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new AttendanceRecordModel());
    }

    public function findOpenCheckIn(string $tenantId, string $employeeId, string $workDate): ?object
    {
        return AttendanceRecordModel::where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('work_date', $workDate)
            ->whereNull('check_out')
            ->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = AttendanceRecordModel::query();

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['work_date'])) {
            $query->where('work_date', $filters['work_date']);
        }

        return $query->orderByDesc('work_date')->orderByDesc('check_in')->paginate($perPage);
    }
}
