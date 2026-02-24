<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\SalaryStructureAssignmentRepositoryInterface;
use Modules\HR\Infrastructure\Models\SalaryStructureAssignmentModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class SalaryStructureAssignmentRepository extends BaseEloquentRepository implements SalaryStructureAssignmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SalaryStructureAssignmentModel());
    }

    public function findActiveByEmployee(string $tenantId, string $employeeId): ?object
    {
        return SalaryStructureAssignmentModel::where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = SalaryStructureAssignmentModel::query();

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['structure_id'])) {
            $query->where('structure_id', $filters['structure_id']);
        }

        return $query->orderByDesc('effective_from')->paginate($perPage);
    }
}
