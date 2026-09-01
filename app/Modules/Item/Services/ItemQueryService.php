<?php

declare(strict_types=1);

namespace Modules\Item\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;

final class ItemQueryService
{
    public function __construct(
        private readonly ItemPriceResolutionService $prices,
        private readonly DecimalMath $math,
    ) {}

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
            'untracked-stockable' => [$criteria['is_stockable'] = true, $criteria['tracking_type'] = TrackingType::None->value],
            'batch-tracked-stockable' => [$criteria['is_stockable'] = true, $criteria['tracking_types'] = [TrackingType::Batch->value, TrackingType::Lot->value]],
            'service', 'labour', 'combo', 'package' => $criteria['item_type'] = $kind,
            default => null,
        };

        $paginator = $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));

        return $this->resolveLookupServicePrices($paginator, $organizationUnitId);
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Item
    {
        $item = $this->baseQuery($tenantId, $organizationUnitId)
            ->with($this->summaryRelations())
            ->find($id);

        if ($item instanceof Item) {
            return $item;
        }

        $this->assertItemIsNotOwnedByAnotherScope($id, $tenantId, $organizationUnitId);
        throw (new ModelNotFoundException)->setModel(Item::class, [$id]);
    }

    public function item(int $id, int $tenantId, ?int $organizationUnitId): Item
    {
        $item = $this->baseQuery($tenantId, $organizationUnitId)->find($id);

        if ($item instanceof Item) {
            return $item;
        }

        $this->assertItemIsNotOwnedByAnotherScope($id, $tenantId, $organizationUnitId);
        throw (new ModelNotFoundException)->setModel(Item::class, [$id]);
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
        return ['category', 'brand', 'tenant.baseCurrency', 'baseUom', 'defaultTaxGroup', 'purchaseTaxGroup', 'salesTaxGroup'];
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

        foreach (['item_type', 'tracking_type', 'is_stockable', 'is_active'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['tracking_types'])) {
            $query->whereIn('tracking_type', $criteria['tracking_types']);
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

    private function resolveLookupServicePrices(
        LengthAwarePaginator $paginator,
        ?int $organizationUnitId,
    ): LengthAwarePaginator {
        /** @var Collection<int, Item> $items */
        $items = $paginator->getCollection();
        $tenantId = $items->first()?->tenant_id;
        $availableStockByItemId = $tenantId === null
            ? []
            : $this->availableStockByItemIds(
                $items->modelKeys(),
                (int) $tenantId,
                $organizationUnitId,
            );

        $items->each(function (Item $item) use ($organizationUnitId, $availableStockByItemId): void {
            $resolvedServicePrice = $this->prices->resolvePrice(
                item: $item,
                context: ItemPriceResolutionService::CONTEXT_SERVICE,
                organizationUnitId: $organizationUnitId,
            );
            $resolvedPurchasePrice = $this->prices->resolvePrice(
                item: $item,
                context: ItemPriceResolutionService::CONTEXT_PURCHASE,
                organizationUnitId: $organizationUnitId,
            );

            $item->setAttribute('resolved_service_unit_price', $resolvedServicePrice->amount ?? $this->math->normalize('0'));
            $item->setAttribute('resolved_purchase_unit_price', $resolvedPurchasePrice->amount);
            $item->setAttribute(
                'available_stock_quantity',
                $item->is_stockable
                    ? ($availableStockByItemId[(int) $item->getKey()] ?? '0.000000')
                    : null,
            );
        });

        return $paginator->setCollection($items);
    }

    /** @param list<int> $itemIds
     * @return array<int, string>
     */
    public function availableStockByItemIds(array $itemIds, int $tenantId, ?int $organizationUnitId): array
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if ($itemIds === []) {
            return [];
        }

        $query = DB::table('inventory_stock_balances')
            ->selectRaw('item_id, SUM(quantity_available) as available_stock_quantity')
            ->where('tenant_id', $tenantId)
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id');

        if ($organizationUnitId === null) {
            $query->whereNull('organization_unit_id');
        } else {
            $query->where(function ($scope) use ($organizationUnitId): void {
                $scope->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
        }

        return $query
            ->pluck('available_stock_quantity', 'item_id')
            ->mapWithKeys(fn ($quantity, $itemId): array => [(int) $itemId => $this->math->normalize((string) $quantity)])
            ->all();
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
            'invoice_lines' => ['item_id'],
            'vehicle_service_job_lines' => ['item_id'],
            'supplier_item_mappings' => ['item_id'],
        ];
    }

    private function assertItemIsNotOwnedByAnotherScope(int $id, int $tenantId, ?int $organizationUnitId): void
    {
        $record = DB::table('items')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first(['tenant_id', 'organization_unit_id']);

        if ($record === null) {
            return;
        }

        if ((int) $record->tenant_id !== $tenantId) {
            throw new AuthorizationException('Item belongs to a different tenant.');
        }

        $recordOrganizationUnitId = $record->organization_unit_id === null
            ? null
            : (int) $record->organization_unit_id;

        if ($organizationUnitId !== null
            && $recordOrganizationUnitId !== null
            && $recordOrganizationUnitId !== $organizationUnitId) {
            throw new AuthorizationException('Item belongs to a different organization unit.');
        }
    }
}
