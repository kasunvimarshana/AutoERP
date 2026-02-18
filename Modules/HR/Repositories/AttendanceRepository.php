<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\Attendance;
use Modules\HR\Repositories\Contracts\AttendanceRepositoryInterface;

/**
 * Attendance Repository Implementation
 *
 * Handles all attendance data access operations.
 */
class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    /**
     * AttendanceRepository constructor.
     */
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEmployeeAndDate(int $employeeId, string $date): ?Attendance
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByEmployee(int $employeeId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->where('employee_id', $employeeId);

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getIncomplete(): Collection
    {
        return $this->model
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->get();
    }
}
