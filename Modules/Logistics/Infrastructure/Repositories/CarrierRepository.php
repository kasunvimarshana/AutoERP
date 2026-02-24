<?php

namespace Modules\Logistics\Infrastructure\Repositories;

use Modules\Logistics\Domain\Contracts\CarrierRepositoryInterface;
use Modules\Logistics\Infrastructure\Models\CarrierModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class CarrierRepository extends BaseEloquentRepository implements CarrierRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new CarrierModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = CarrierModel::query();

        if (! empty($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('code', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return CarrierModel::where('tenant_id', $tenantId)->where('code', $code)->first();
    }
}
