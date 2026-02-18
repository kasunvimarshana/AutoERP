<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\Payroll;
use Modules\HR\Repositories\Contracts\PayrollRepositoryInterface;

/**
 * Payroll Repository Implementation
 *
 * Handles all payroll data access operations.
 */
class PayrollRepository extends BaseRepository implements PayrollRepositoryInterface
{
    /**
     * PayrollRepository constructor.
     */
    public function __construct(Payroll $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByPayrollNumber(string $payrollNumber): ?Payroll
    {
        return $this->model->where('payroll_number', $payrollNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model->where('employee_id', $employeeId)->orderBy('year', 'desc')->orderBy('month', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByMonthYear(int $month, int $year): Collection
    {
        return $this->model->where('month', $month)->where('year', $year)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByEmployeeMonthYear(int $employeeId, int $month, int $year): ?Payroll
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }
}
