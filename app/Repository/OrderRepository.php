<?php

namespace App\Repository;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class OrderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Order);
    }

    public function findByTenant(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->with('lines');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findByStatus(string $tenantId, OrderStatus $status): Collection
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get();
    }
}
