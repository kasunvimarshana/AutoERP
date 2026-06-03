<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\InventoryCostLayerRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;

final class EloquentInventoryCostLayerRepository extends EloquentRepository implements InventoryCostLayerRepositoryInterface
{
    public function __construct(InventoryCostLayerModel $model)
    {
        parent::__construct($model);
    }

    public function listOpenLayers(array $criteria, string $valuationMethod, int $limit = 1000): array
    {
        $lotNumber = $criteria[InventoryDimension::LOT_NUMBER] ?? null;
        unset($criteria[InventoryDimension::LOT_NUMBER]);

        $query = $this->applyCriteria($this->query()->select('inventory_cost_layers.*'), $this->qualifyCriteria($criteria))
            ->where('inventory_cost_layers.quantity_remaining', '>', 0);

        if (is_string($lotNumber) && trim($lotNumber) !== '') {
            $query
                ->join('batches as inventory_cost_layer_lots', 'inventory_cost_layers.batch_id', '=', 'inventory_cost_layer_lots.id')
                ->select('inventory_cost_layers.*')
                ->where('inventory_cost_layer_lots.lot_number', $lotNumber);
        }

        $method = strtolower(trim($valuationMethod));
        if ($method !== '') {
            $query->where(function ($builder) use ($method): void {
                $builder
                    ->where('inventory_cost_layers.valuation_method', $method)
                    ->orWhereNull('inventory_cost_layers.valuation_method');
            });
        }

        $this->applyConfiguredOrdering($query, $method);

        $models = $query->limit(max(1, $limit))->get();
        $records = [];
        foreach ($models as $model) {
            $records[] = $this->toRecord($model);
        }

        return $records;
    }

    private function applyConfiguredOrdering(Builder $query, string $method): void
    {
        $profile = (array) config(
            sprintf('inventory.engines.valuation.methods.%s', $method),
            config('inventory.engines.valuation.methods.default', []),
        );
        $ordering = (array) ($profile['ordering'] ?? []);

        if ($ordering === []) {
            $query->orderBy('inventory_cost_layers.id');

            return;
        }

        foreach ($ordering as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $column = $this->safeColumn($rule['column'] ?? null);
            if ($column === null) {
                continue;
            }

            if (($rule['nulls_last'] ?? false) === true) {
                $query->orderByRaw(sprintf('CASE WHEN %s IS NULL THEN 1 ELSE 0 END', $column));
            }

            strtolower((string) ($rule['direction'] ?? 'asc')) === 'desc'
                ? $query->orderByDesc($column)
                : $query->orderBy($column);
        }
    }

    private function safeColumn(mixed $column): ?string
    {
        if (! is_string($column) || trim($column) === '') {
            return null;
        }

        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column) === 1
            ? $column
            : null;
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function qualifyCriteria(array $criteria): array
    {
        $qualified = [];
        foreach ($criteria as $column => $value) {
            if (! is_string($column) || str_contains($column, '.')) {
                $qualified[$column] = $value;

                continue;
            }

            $qualified['inventory_cost_layers.'.$column] = $value;
        }

        return $qualified;
    }
}
