<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollModel;

class EloquentPayrollRepository implements PayrollRepositoryInterface
{
    public function __construct(private readonly PayrollModel $model) {}

    private function toEntity(PayrollModel $m): PayrollRecord
    {
        return new PayrollRecord(
            $m->id,
            $m->tenant_id,
            $m->employee_id,
            (int) $m->period_year,
            (int) $m->period_month,
            (float) $m->basic_salary,
            (float) $m->allowances,
            (float) $m->deductions,
            (float) $m->tax_amount,
            (float) $m->net_salary,
            $m->status,
            $m->payment_date,
            $m->payment_reference,
            $m->breakdown,
            $m->processed_by_id,
            $m->processed_at,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?PayrollRecord
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByTenantAndPeriod(int $tenantId, int $year, int $month): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findByEmployeeAndPeriod(int $employeeId, int $year, int $month): ?PayrollRecord
    {
        $m = $this->model->newQuery()
            ->where('employee_id', $employeeId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): PayrollRecord
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?PayrollRecord
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
