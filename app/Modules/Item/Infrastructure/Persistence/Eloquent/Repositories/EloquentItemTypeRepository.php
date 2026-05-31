<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ItemTypeRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemTypeModel;

final class EloquentItemTypeRepository extends EloquentRepository implements ItemTypeRepositoryInterface
{
    public function __construct(ItemTypeModel $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function pageForTenant(int $tenantId, array $criteria, int $perPage, int $page): PagedResult
    {
        $query = $this->query()
            ->where(function (Builder $tenantScope) use ($tenantId): void {
                $tenantScope->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            });

        $query = $this->applyItemTypeCriteria($query, $criteria);

        $paginator = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

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

    /**
     * @param array<string, mixed> $criteria
     */
    private function applyItemTypeCriteria(Builder $query, array $criteria): Builder
    {
        foreach ($criteria as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($field === 'search') {
                $needle = '%' . str_replace('%', '\\%', (string) $value) . '%';
                $query->where(function (Builder $search) use ($needle): void {
                    $search->where('code', 'like', $needle)
                        ->orWhere('name', 'like', $needle);
                });

                continue;
            }

            if (in_array($field, ['code', 'is_active', 'is_chargeable', 'is_rentable', 'is_service', 'is_stockable'], true)) {
                $query->where($field, $value);
            }
        }

        return $query;
    }
}
