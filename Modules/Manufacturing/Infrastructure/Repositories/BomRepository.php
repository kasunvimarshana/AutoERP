<?php

namespace Modules\Manufacturing\Infrastructure\Repositories;

use Modules\Manufacturing\Domain\Contracts\BomRepositoryInterface;
use Modules\Manufacturing\Infrastructure\Models\BomModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class BomRepository extends BaseEloquentRepository implements BomRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new BomModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = BomModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findActiveByProduct(string $tenantId, string $productId): ?object
    {
        return BomModel::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->first();
    }
}
