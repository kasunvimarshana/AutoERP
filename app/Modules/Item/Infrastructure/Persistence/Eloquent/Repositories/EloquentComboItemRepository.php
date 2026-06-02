<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;

final class EloquentComboItemRepository extends EloquentRepository implements ComboItemRepositoryInterface
{
    public function __construct(ComboItemModel $model)
    {
        parent::__construct($model);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()->where('tenant_id', $tenantId)->find($id);

        if ($model === null) {
            return null;
        }

        $records = $this->recordsFromRows([$model->toArray()]);

        return $records[0] ?? null;
    }

    public function list(array $criteria = [], array $with = []): array
    {
        $models = $criteria === []
            ? $this->query($with)->get()
            : $this->applyCriteria($this->query($with), $criteria)->get();

        return $this->recordsFromRows($models->map(fn ($model): array => $model->toArray())->all());
    }

    public function page(array $criteria, int $perPage, int $page, array $with = []): PagedResult
    {
        $query = $criteria === [] ? $this->query($with) : $this->applyCriteria($this->query($with), $criteria);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new PagedResult(
            $this->recordsFromRows(array_map(fn ($model): array => $model->toArray(), $paginator->items())),
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }

    public function introducesCycle(
        int $tenantId,
        int $comboItemId,
        int $componentItemId,
        ?int $ignoreLinkId = null,
    ): bool {
        if ($comboItemId === $componentItemId) {
            return true;
        }

        $stack = [$componentItemId];
        $visited = [];

        while ($stack !== []) {
            $current = (int) array_pop($stack);
            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;
            if ($current === $comboItemId) {
                return true;
            }

            $childrenQuery = DB::table('combo_items')
                ->select('component_item_id')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('combo_item_id', $current);

            if ($ignoreLinkId !== null) {
                $childrenQuery->where('id', '!=', $ignoreLinkId);
            }

            $children = $childrenQuery->pluck('component_item_id')->all();
            foreach ($children as $child) {
                $childId = (int) $child;
                if (! isset($visited[$childId])) {
                    $stack[] = $childId;
                }
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<DataRecord>
     */
    private function recordsFromRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $itemIds = [];
        $uomIds = [];
        foreach ($rows as $row) {
            if (isset($row['component_item_id']) && is_numeric($row['component_item_id'])) {
                $itemIds[] = (int) $row['component_item_id'];
            }
            if (isset($row['uom_id']) && is_numeric($row['uom_id'])) {
                $uomIds[] = (int) $row['uom_id'];
            }
        }

        $items = DB::table('items')
            ->whereIn('id', array_values(array_unique($itemIds)))
            ->get(['id', 'sku', 'name', 'type', 'is_stockable'])
            ->keyBy('id');

        $uoms = DB::table('unit_of_measures')
            ->whereIn('id', array_values(array_unique($uomIds)))
            ->get(['id', 'code', 'name', 'symbol'])
            ->keyBy('id');

        $records = [];
        foreach ($rows as $row) {
            $componentId = isset($row['component_item_id']) ? (int) $row['component_item_id'] : 0;
            $uomId = isset($row['uom_id']) ? (int) $row['uom_id'] : 0;
            $component = $items->get($componentId);
            $uom = $uoms->get($uomId);

            if ($component !== null) {
                $row['component_item_sku'] = $component->sku;
                $row['component_item_name'] = $component->name;
                $row['component_item_type'] = $component->type;
                $row['component_is_stockable'] = (bool) $component->is_stockable;
            }

            if ($uom !== null) {
                $row['uom_code'] = $uom->code;
                $row['uom_name'] = $uom->name;
                $row['uom_symbol'] = $uom->symbol;
            }

            $records[] = new DataRecord($row);
        }

        return $records;
    }
}
