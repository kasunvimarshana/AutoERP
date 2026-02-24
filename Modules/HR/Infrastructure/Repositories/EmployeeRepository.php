<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Infrastructure\Models\EmployeeModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class EmployeeRepository extends BaseEloquentRepository implements EmployeeRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new EmployeeModel());
    }

    public function findByEmail(string $tenantId, string $email): ?object
    {
        return EmployeeModel::where('tenant_id', $tenantId)->where('email', $email)->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = EmployeeModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->orderBy('last_name')->paginate($perPage);
    }

    public function chunkActive(string $tenantId, int $chunkSize, callable $callback): void
    {
        EmployeeModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->chunk($chunkSize, $callback);
    }
}
