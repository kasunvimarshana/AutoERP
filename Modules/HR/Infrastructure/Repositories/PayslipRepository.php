<?php

namespace Modules\HR\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\HR\Domain\Contracts\PayslipRepositoryInterface;
use Modules\HR\Infrastructure\Models\PayslipModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class PayslipRepository extends BaseEloquentRepository implements PayslipRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new PayslipModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = PayslipModel::query();

        if (! empty($filters['payroll_run_id'])) {
            $query->where('payroll_run_id', $filters['payroll_run_id']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByPayrollRun(string $payrollRunId): Collection
    {
        return PayslipModel::where('payroll_run_id', $payrollRunId)->get();
    }
}
