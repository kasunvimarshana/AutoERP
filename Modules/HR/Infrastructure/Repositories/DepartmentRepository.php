<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\DepartmentRepositoryInterface;
use Modules\HR\Infrastructure\Models\DepartmentModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class DepartmentRepository extends BaseEloquentRepository implements DepartmentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new DepartmentModel());
    }

    public function findByName(string $tenantId, string $name): ?object
    {
        return DepartmentModel::where('tenant_id', $tenantId)->where('name', $name)->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = DepartmentModel::query();

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
