<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceModel;

class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function __construct(private readonly AttendanceModel $model) {}

    private function toEntity(AttendanceModel $m): AttendanceRecord
    {
        return new AttendanceRecord(
            $m->id,
            $m->tenant_id,
            $m->employee_id,
            $m->attendance_date,
            $m->check_in,
            $m->check_out,
            $m->worked_hours !== null ? (float) $m->worked_hours : null,
            $m->source,
            $m->device_id,
            $m->biometric_data,
            $m->notes,
            (bool) $m->is_approved,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?AttendanceRecord
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->orderBy('attendance_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByEmployeeAndDate(int $employeeId, string $date): ?AttendanceRecord
    {
        $m = $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenantAndDateRange(int $tenantId, string $startDate, string $endDate, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): AttendanceRecord
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?AttendanceRecord
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }
}
