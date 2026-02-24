<?php

namespace Modules\Manufacturing\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Manufacturing\Domain\Contracts\WorkOrderRepositoryInterface;
use Modules\Manufacturing\Infrastructure\Models\WorkOrderModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class WorkOrderRepository extends BaseEloquentRepository implements WorkOrderRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new WorkOrderModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = WorkOrderModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['bom_id'])) {
            $query->where('bom_id', $filters['bom_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByStatus(string $tenantId, string $status): Collection
    {
        return WorkOrderModel::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();
    }
}
