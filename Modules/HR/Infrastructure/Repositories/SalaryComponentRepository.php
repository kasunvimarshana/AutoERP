<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\SalaryComponentRepositoryInterface;
use Modules\HR\Infrastructure\Models\SalaryComponentModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class SalaryComponentRepository extends BaseEloquentRepository implements SalaryComponentRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SalaryComponentModel());
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return SalaryComponentModel::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = SalaryComponentModel::query();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('code')->paginate($perPage);
    }
}
