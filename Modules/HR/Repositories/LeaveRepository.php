<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\Leave;
use Modules\HR\Repositories\Contracts\LeaveRepositoryInterface;

/**
 * Leave Repository Implementation
 *
 * Handles all leave data access operations.
 */
class LeaveRepository extends BaseRepository implements LeaveRepositoryInterface
{
    /**
     * LeaveRepository constructor.
     */
    public function __construct(Leave $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->where('employee_id', $employeeId)->orderBy('start_date', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingLeaves(): Collection
    {
        return $this->model->where('status', 'pending')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLeaveBalance(int $employeeId, int $leaveTypeId, int $year): float
    {
        $totalUsed = $this->model
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days');

        return (float) $totalUsed;
    }
}
