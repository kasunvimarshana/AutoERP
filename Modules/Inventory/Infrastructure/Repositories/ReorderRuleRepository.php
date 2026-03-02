<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Inventory\Domain\Contracts\ReorderRuleRepositoryInterface;
use Modules\Inventory\Domain\Entities\ReorderRule;
use Modules\Inventory\Infrastructure\Models\ReorderRuleModel;

class ReorderRuleRepository extends BaseRepository implements ReorderRuleRepositoryInterface
{
    protected function model(): string
    {
        return ReorderRuleModel::class;
    }

    public function save(ReorderRule $rule): ReorderRule
    {
        if ($rule->id !== null) {
            $model = $this->newQuery()->where('tenant_id', $rule->tenantId)->findOrFail($rule->id);
        } else {
            $model = new ReorderRuleModel;
        }

        $model->tenant_id = $rule->tenantId;
        $model->product_id = $rule->productId;
        $model->warehouse_id = $rule->warehouseId;
        $model->reorder_point = $rule->reorderPoint;
        $model->reorder_quantity = $rule->reorderQuantity;
        $model->is_active = $rule->isActive;
        $model->save();

        return $this->toDomain($model);
    }

    public function existsForProductAndWarehouse(int $tenantId, int $productId, int $warehouseId): bool
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->exists();
    }

    public function findById(int $tenantId, int $id): ?ReorderRule
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(
        int $tenantId,
        ?int $productId,
        ?int $warehouseId,
        bool $activeOnly,
        int $page,
        int $perPage
    ): array {
        $query = $this->newQuery()->where('tenant_id', $tenantId);

        if ($productId !== null) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId !== null) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (ReorderRuleModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function delete(int $tenantId, int $id): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    public function findLowStockItems(
        int $tenantId,
        ?int $warehouseId,
        int $page,
        int $perPage
    ): array {
        $query = ReorderRuleModel::query()
            ->select([
                'inventory_reorder_rules.*',
                'stock_balances.quantity_on_hand',
            ])
            ->leftJoin('stock_balances', function ($join): void {
                $join->on('stock_balances.tenant_id', '=', 'inventory_reorder_rules.tenant_id')
                    ->on('stock_balances.product_id', '=', 'inventory_reorder_rules.product_id')
                    ->on('stock_balances.warehouse_id', '=', 'inventory_reorder_rules.warehouse_id');
            })
            ->where('inventory_reorder_rules.tenant_id', $tenantId)
            ->where('inventory_reorder_rules.is_active', true)
            ->where(function ($q): void {
                $q->whereNull('stock_balances.quantity_on_hand')
                    ->orWhereColumn('stock_balances.quantity_on_hand', '<=', 'inventory_reorder_rules.reorder_point');
            });

        if ($warehouseId !== null) {
            $query->where('inventory_reorder_rules.warehouse_id', $warehouseId);
        }

        $paginator = $query->orderByDesc('inventory_reorder_rules.id')
            ->paginate($perPage, ['inventory_reorder_rules.*', 'stock_balances.quantity_on_hand'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(function (ReorderRuleModel $m): array {
                    return [
                        'rule' => $this->toDomain($m),
                        'quantity_on_hand' => $m->quantity_on_hand !== null
                            ? bcadd((string) $m->quantity_on_hand, '0', 4)
                            : '0.0000',
                    ];
                })
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    private function toDomain(ReorderRuleModel $model): ReorderRule
    {
        return new ReorderRule(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            warehouseId: $model->warehouse_id,
            reorderPoint: bcadd((string) $model->reorder_point, '0', 4),
            reorderQuantity: bcadd((string) $model->reorder_quantity, '0', 4),
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
