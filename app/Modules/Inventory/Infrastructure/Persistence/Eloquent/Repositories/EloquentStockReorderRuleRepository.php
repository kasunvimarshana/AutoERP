<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\StockReorderRule;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReorderRuleRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReorderRuleModel;
use RuntimeException;

class EloquentStockReorderRuleRepository implements StockReorderRuleRepositoryInterface
{
    public function create(StockReorderRule $rule): StockReorderRule
    {
        $model = StockReorderRuleModel::create([
            'tenant_id'       => $rule->getTenantId(),
            'product_id'      => $rule->getProductId(),
            'variant_id'      => $rule->getVariantId(),
            'warehouse_id'    => $rule->getWarehouseId(),
            'minimum_quantity'=> $rule->getMinimumQuantity(),
            'maximum_quantity'=> $rule->getMaximumQuantity(),
            'reorder_quantity'=> $rule->getReorderQuantity(),
            'is_active'       => $rule->isActive(),
        ]);

        $rule->setId($model->id);

        return $rule;
    }

    public function update(StockReorderRule $rule): StockReorderRule
    {
        $model = StockReorderRuleModel::where('tenant_id', $rule->getTenantId())
            ->findOrFail($rule->getId());

        $model->update([
            'product_id'      => $rule->getProductId(),
            'variant_id'      => $rule->getVariantId(),
            'warehouse_id'    => $rule->getWarehouseId(),
            'minimum_quantity'=> $rule->getMinimumQuantity(),
            'maximum_quantity'=> $rule->getMaximumQuantity(),
            'reorder_quantity'=> $rule->getReorderQuantity(),
            'is_active'       => $rule->isActive(),
        ]);

        return $rule;
    }

    public function delete(int $tenantId, int $id): void
    {
        StockReorderRuleModel::where('tenant_id', $tenantId)->findOrFail($id)->delete();
    }

    public function findById(int $tenantId, int $id): StockReorderRule
    {
        $model = StockReorderRuleModel::where('tenant_id', $tenantId)->findOrFail($id);

        return $this->toDomain($model);
    }

    public function listByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return StockReorderRuleModel::where('tenant_id', $tenantId)
            ->orderBy('id')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function listBelowMinimum(int $tenantId): array
    {
        $rows = DB::table('stock_reorder_rules as r')
            ->join('stock_levels as sl', function ($join) {
                $join->on('sl.tenant_id', '=', 'r.tenant_id')
                    ->on('sl.product_id', '=', 'r.product_id')
                    ->whereColumn('sl.tenant_id', 'r.tenant_id');
            })
            ->join('warehouses as w', 'w.id', '=', 'r.warehouse_id')
            ->join('warehouse_locations as wl', function ($join) {
                $join->on('wl.warehouse_id', '=', 'r.warehouse_id')
                    ->on('wl.id', '=', 'sl.location_id');
            })
            ->where('r.tenant_id', $tenantId)
            ->where('r.is_active', true)
            ->whereRaw('sl.quantity_available < r.minimum_quantity')
            ->select([
                'r.id',
                'r.product_id',
                'r.variant_id',
                'r.warehouse_id',
                'w.name as warehouse_name',
                'r.minimum_quantity',
                'r.reorder_quantity',
                DB::raw('SUM(sl.quantity_available) as current_quantity'),
            ])
            ->groupBy('r.id', 'r.product_id', 'r.variant_id', 'r.warehouse_id', 'w.name', 'r.minimum_quantity', 'r.reorder_quantity')
            ->get()
            ->toArray();

        return $rows;
    }

    private function toDomain(StockReorderRuleModel $model): StockReorderRule
    {
        return new StockReorderRule(
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            variantId: $model->variant_id,
            warehouseId: $model->warehouse_id,
            minimumQuantity: (string) $model->minimum_quantity,
            maximumQuantity: $model->maximum_quantity !== null ? (string) $model->maximum_quantity : null,
            reorderQuantity: (string) $model->reorder_quantity,
            isActive: (bool) $model->is_active,
            id: $model->id,
        );
    }
}
