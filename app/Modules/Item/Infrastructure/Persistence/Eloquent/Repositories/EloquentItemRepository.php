<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;

final class EloquentItemRepository extends EloquentRepository implements ItemRepositoryInterface
{
    public function __construct(ItemModel $model)
    {
        parent::__construct($model);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()->where('tenant_id', $tenantId)->find($id);

        return $model === null ? null : $this->toRecord($model);
    }

    /**
     * @return list<DataRecord>
     */
    public function list(array $criteria = [], array $with = []): array
    {
        $query = $this->applyItemCriteria($this->query($with), $criteria);
        $models = $query->get();

        $records = [];
        foreach ($models as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    public function page(array $criteria, int $perPage, int $page, array $with = []): PagedResult
    {
        $query = $this->applyItemCriteria($this->query($with), $criteria);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    private function applyItemCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($column === 'search') {
                $search = trim((string) $value);
                if ($search === '') {
                    continue;
                }

                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%');
                });
                continue;
            }

            if (is_array($value)) {
                if ($value === []) {
                    $query->whereRaw('1 = 0');
                    continue;
                }

                $query->whereIn($column, $value);
                continue;
            }

            $query->where($column, $value);
        }

        return $query;
    }
}
