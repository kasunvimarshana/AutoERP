<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Item\Models\Item;

final class ItemQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)->with($this->summaryRelations());
        $this->applyCriteria($query, $criteria);

        $sort = in_array(($criteria['sort'] ?? null), ['code', 'name', 'item_type', 'created_at'], true)
            ? (string) $criteria['sort']
            : 'name';
        $direction = ($criteria['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage, string $kind): LengthAwarePaginator
    {
        $criteria['is_active'] = true;
        match ($kind) {
            'stockable' => $criteria['is_stockable'] = true,
            'service', 'labour', 'combo', 'package' => $criteria['item_type'] = $kind,
            default => null,
        };

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Item
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->with($this->summaryRelations())
            ->findOrFail($id);
    }

    public function item(int $id, int $tenantId, ?int $organizationUnitId): Item
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function delete(Item $item): void
    {
        $this->assertUnused($item);
        $item->delete();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Item::query()->forTenant($tenantId, $organizationUnitId);
    }

    /**
     * @return list<string>
     */
    private function summaryRelations(): array
    {
        return ['category', 'brand', 'tenant.currency', 'baseUom', 'defaultTaxGroup', 'purchaseTaxGroup', 'salesTaxGroup'];
    }

    private function applyCriteria(Builder $query, array $criteria): void
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        foreach (['item_type', 'is_stockable', 'is_active'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['category_id'])) {
            $query->where('item_category_id', (int) $criteria['category_id']);
        }
        if (! empty($criteria['brand_id'])) {
            $query->where('item_brand_id', (int) $criteria['brand_id']);
        }
        if (! empty($criteria['module_code'])) {
            $query->whereHas('usageRules', fn (Builder $rules): Builder => $rules
                ->where('module_code', (string) $criteria['module_code'])
                ->where('is_enabled', true));
        }
    }

    private function assertUnused(Item $item): void
    {
        foreach ($this->referenceColumns() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $query = DB::table($table)
                    ->where('tenant_id', $item->tenant_id)
                    ->where($column, $item->getKey());
                if (Schema::hasColumn($table, 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }
                if ($query->exists()) {
                    throw new InvalidArgumentException('Item cannot be deleted while related setup or transaction records reference it. Deactivate the item instead.');
                }
            }
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function referenceColumns(): array
    {
        return [
            'item_units' => ['item_id'],
            'item_variants' => ['item_id'],
            'item_bundles' => ['parent_item_id', 'child_item_id'],
            'item_prices' => ['item_id'],
            'item_codes' => ['item_id'],
            'item_usage_rules' => ['item_id'],
            'inventory_stock_balances' => ['item_id'],
            'inventory_movements' => ['item_id'],
            'inventory_batches' => ['item_id'],
            'inventory_serial_numbers' => ['item_id'],
            'inventory_reservations' => ['item_id'],
            'inventory_allocations' => ['item_id'],
            'inventory_valuation_layers' => ['item_id'],
            'purchase_order_lines' => ['item_id'],
            'goods_receipt_note_lines' => ['item_id'],
            'purchase_return_lines' => ['item_id'],
            'sales_order_lines' => ['item_id'],
            'sales_delivery_lines' => ['item_id'],
            'sales_return_lines' => ['item_id'],
            'invoice_lines' => ['item_id'],
            'vehicle_service_job_lines' => ['item_id'],
            'supplier_item_mappings' => ['item_id'],
        ];
    }
}
