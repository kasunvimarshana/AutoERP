<?php

declare(strict_types=1);

namespace Modules\Warehouse\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Repositories\EloquentRepository;
use Modules\Warehouse\Models\WarehouseLocationModel;

final class EloquentWarehouseLocationRepository extends EloquentRepository implements WarehouseLocationRepositoryInterface
{
    public function __construct(WarehouseLocationModel $model)
    {
        parent::__construct($model);
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        unset($criteria['search']);

        parent::applyCriteria($query, $criteria);

        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
