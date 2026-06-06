<?php

declare(strict_types=1);

namespace Modules\UOM\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\UOM\Models\UnitOfMeasureModel;

final class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        return $model !== null ? $this->toRecord($model) : null;
    }

    public function findByCode(string $code, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null ? $this->toRecord($model) : null;
    }

    public function findBySymbol(string $symbol, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('symbol', $symbol)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null ? $this->toRecord($model) : null;
    }

    public function findBaseUomForType(string $type, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_base', true)
            ->first();

        return $model !== null ? $this->toRecord($model) : null;
    }

    public function searchByNameOrSymbol(string $search, int $tenantId, int $perPage, int $page): PagedResult
    {
        $paginator = $this->query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('symbol', 'like', '%'.$search.'%');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            $items[] = $this->toRecord($model);
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        if (array_key_exists('search', $criteria)) {
            $search = (string) $criteria['search'];
            unset($criteria['search']);

            $query->where(function (Builder $q) use ($search): void {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('symbol', 'like', '%'.$search.'%');
            });
        }

        return parent::applyCriteria($query, $criteria);
    }
}
