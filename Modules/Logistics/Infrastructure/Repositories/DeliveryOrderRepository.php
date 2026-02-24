<?php

namespace Modules\Logistics\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Logistics\Domain\Contracts\DeliveryOrderRepositoryInterface;
use Modules\Logistics\Infrastructure\Models\DeliveryOrderModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class DeliveryOrderRepository extends BaseEloquentRepository implements DeliveryOrderRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new DeliveryOrderModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = DeliveryOrderModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['carrier_id'])) {
            $query->where('carrier_id', $filters['carrier_id']);
        }
        if (! empty($filters['reference_no'])) {
            $query->where('reference_no', 'like', '%' . $filters['reference_no'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByStatus(string $tenantId, string $status): Collection
    {
        return DeliveryOrderModel::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();
    }

    public function countByTenantAndYear(string $tenantId, int $year): int
    {
        return DeliveryOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();
    }
}
