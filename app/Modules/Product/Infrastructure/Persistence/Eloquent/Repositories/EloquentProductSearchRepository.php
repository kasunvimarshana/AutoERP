<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchRepositoryInterface;

class EloquentProductSearchRepository implements ProductSearchRepositoryInterface
{
    public function searchCatalog(array $criteria): LengthAwarePaginator
    {
        $tenantId = (int) $criteria['tenant_id'];

        $stockSummary = $this->buildStockSummaryQuery($criteria);

        $query = DB::table('products as p')
            ->leftJoin('product_variants as pv', function ($join) use ($tenantId): void {
                $join->on('pv.product_id', '=', 'p.id')
                    ->where('pv.tenant_id', '=', $tenantId)
                    ->whereNull('pv.deleted_at')
                    ->where('pv.is_active', '=', true);
            })
            ->leftJoin('product_categories as pc', function ($join) use ($tenantId): void {
                $join->on('pc.id', '=', 'p.category_id')
                    ->where('pc.tenant_id', '=', $tenantId)
                    ->whereNull('pc.deleted_at');
            })
            ->leftJoin('product_brands as pb', function ($join) use ($tenantId): void {
                $join->on('pb.id', '=', 'p.brand_id')
                    ->where('pb.tenant_id', '=', $tenantId)
                    ->whereNull('pb.deleted_at');
            })
            ->leftJoin('units_of_measure as base_uom', function ($join) use ($tenantId): void {
                $join->on('base_uom.id', '=', 'p.base_uom_id')
                    ->where('base_uom.tenant_id', '=', $tenantId)
                    ->whereNull('base_uom.deleted_at');
            })
            ->leftJoin('units_of_measure as sales_uom', function ($join) use ($tenantId): void {
                $join->on('sales_uom.id', '=', 'p.sales_uom_id')
                    ->where('sales_uom.tenant_id', '=', $tenantId)
                    ->whereNull('sales_uom.deleted_at');
            })
            ->leftJoin('units_of_measure as purchase_uom', function ($join) use ($tenantId): void {
                $join->on('purchase_uom.id', '=', 'p.purchase_uom_id')
                    ->where('purchase_uom.tenant_id', '=', $tenantId)
                    ->whereNull('purchase_uom.deleted_at');
            })
            ->leftJoinSub($stockSummary, 'ss', function ($join): void {
                $join->on('ss.product_id', '=', 'p.id')
                    ->where(function ($where): void {
                        $where->whereColumn('ss.variant_id', 'pv.id')
                            ->orWhere(function ($variantFallback): void {
                                $variantFallback->whereNull('ss.variant_id')
                                    ->whereNull('pv.id');
                            });
                    });
            })
            ->where('p.tenant_id', $tenantId)
            ->whereNull('p.deleted_at');

        if (! (bool) $criteria['include_inactive']) {
            $query->where('p.is_active', true);
        }

        if ($criteria['category_id'] !== null) {
            $query->where('p.category_id', (int) $criteria['category_id']);
        }

        if ($criteria['brand_id'] !== null) {
            $query->where('p.brand_id', (int) $criteria['brand_id']);
        }

        if ($criteria['variant_id'] !== null) {
            $query->where('pv.id', (int) $criteria['variant_id']);
        }

        if ($criteria['product_type'] !== null && $criteria['product_type'] !== '') {
            $query->where('p.type', (string) $criteria['product_type']);
        }

        $this->applyTermSearch($query, $criteria);
        $this->applyStockFilter($query, $criteria);

        $query->select([
            'p.id as product_id',
            'p.name as product_name',
            'p.slug as product_slug',
            'p.sku as product_sku',
            'p.type as product_type',
            'p.is_active',
            'p.metadata as product_metadata',
            'p.category_id',
            'pc.name as category_name',
            'p.brand_id',
            'pb.name as brand_name',
            'p.base_uom_id',
            'base_uom.name as base_uom_name',
            'base_uom.symbol as base_uom_symbol',
            'p.purchase_uom_id',
            'purchase_uom.name as purchase_uom_name',
            'purchase_uom.symbol as purchase_uom_symbol',
            'p.sales_uom_id',
            'sales_uom.name as sales_uom_name',
            'sales_uom.symbol as sales_uom_symbol',
            'pv.id as variant_id',
            'pv.name as variant_name',
            'pv.sku as variant_sku',
            'pv.is_default as variant_is_default',
            DB::raw('COALESCE(ss.quantity_on_hand, 0) as quantity_on_hand'),
            DB::raw('COALESCE(ss.quantity_reserved, 0) as quantity_reserved'),
            DB::raw('COALESCE(ss.quantity_available, 0) as available_quantity'),
            DB::raw('COALESCE(ss.unit_cost, p.standard_cost, 0) as unit_cost'),
        ]);

        $this->applySort($query, $criteria);

        $paginated = $query->paginate((int) $criteria['per_page'], ['*'], 'page', (int) $criteria['page']);

        return $this->hydrateRelations($paginated, $tenantId, $criteria);
    }

    private function buildStockSummaryQuery(array $criteria): \Illuminate\Database\Query\Builder
    {
        $warehouseId = $criteria['warehouse_id'] !== null ? (int) $criteria['warehouse_id'] : null;

        $query = DB::table('stock_levels as sl')
            ->join('warehouse_locations as wl', function ($join): void {
                $join->on('wl.id', '=', 'sl.location_id');
            })
            ->where('sl.tenant_id', (int) $criteria['tenant_id'])
            ->groupBy('sl.product_id', 'sl.variant_id')
            ->select([
                'sl.product_id',
                'sl.variant_id',
                DB::raw('SUM(sl.quantity_on_hand) as quantity_on_hand'),
                DB::raw('SUM(sl.quantity_reserved) as quantity_reserved'),
                DB::raw('SUM(sl.quantity_available) as quantity_available'),
                DB::raw('MAX(sl.unit_cost) as unit_cost'),
            ]);

        if ($warehouseId !== null) {
            $query->where('wl.warehouse_id', $warehouseId);
        }

        return $query;
    }

    private function applyTermSearch(\Illuminate\Database\Query\Builder $query, array $criteria): void
    {
        $term = (string) $criteria['term'];

        if ($term === '') {
            return;
        }

        $tenantId = (int) $criteria['tenant_id'];
        $like = '%'.$term.'%';

        $query->where(function ($where) use ($tenantId, $like): void {
            $where->where('p.name', 'like', $like)
                ->orWhere('p.sku', 'like', $like)
                ->orWhere('pv.sku', 'like', $like)
                ->orWhere('pv.name', 'like', $like)
                ->orWhereExists(function ($identifierSub) use ($tenantId, $like): void {
                    $identifierSub->selectRaw('1')
                        ->from('product_identifiers as pi')
                        ->where('pi.tenant_id', '=', $tenantId)
                        ->whereNull('pi.deleted_at')
                        ->where('pi.is_active', '=', true)
                        ->where('pi.value', 'like', $like)
                        ->whereColumn('pi.product_id', 'p.id')
                        ->where(function ($link): void {
                            $link->whereColumn('pi.variant_id', 'pv.id')
                                ->orWhere(function ($fallback): void {
                                    $fallback->whereNull('pi.variant_id')
                                        ->whereNull('pv.id');
                                });
                        });
                })
                ->orWhereExists(function ($batchSub) use ($tenantId, $like): void {
                    $batchSub->selectRaw('1')
                        ->from('batches as b')
                        ->where('b.tenant_id', '=', $tenantId)
                        ->where('b.batch_number', 'like', $like)
                        ->whereColumn('b.product_id', 'p.id')
                        ->where(function ($link): void {
                            $link->whereColumn('b.variant_id', 'pv.id')
                                ->orWhere(function ($fallback): void {
                                    $fallback->whereNull('b.variant_id')
                                        ->whereNull('pv.id');
                                });
                        });
                })
                ->orWhereExists(function ($lotSub) use ($tenantId, $like): void {
                    $lotSub->selectRaw('1')
                        ->from('batches as b')
                        ->where('b.tenant_id', '=', $tenantId)
                        ->where('b.lot_number', 'like', $like)
                        ->whereColumn('b.product_id', 'p.id')
                        ->where(function ($link): void {
                            $link->whereColumn('b.variant_id', 'pv.id')
                                ->orWhere(function ($fallback): void {
                                    $fallback->whereNull('b.variant_id')
                                        ->whereNull('pv.id');
                                });
                        });
                })
                ->orWhereExists(function ($serialSub) use ($tenantId, $like): void {
                    $serialSub->selectRaw('1')
                        ->from('serials as s')
                        ->where('s.tenant_id', '=', $tenantId)
                        ->where('s.serial_number', 'like', $like)
                        ->whereColumn('s.product_id', 'p.id')
                        ->where(function ($link): void {
                            $link->whereColumn('s.variant_id', 'pv.id')
                                ->orWhere(function ($fallback): void {
                                    $fallback->whereNull('s.variant_id')
                                        ->whereNull('pv.id');
                                });
                        });
                })
                ->orWhereExists(function ($attributeSub) use ($tenantId, $like): void {
                    $attributeSub->selectRaw('1')
                        ->from('variant_attribute_values as vav')
                        ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
                        ->where('vav.tenant_id', '=', $tenantId)
                        ->where('av.tenant_id', '=', $tenantId)
                        ->whereNull('av.deleted_at')
                        ->where('av.value', 'like', $like)
                        ->whereColumn('vav.variant_id', 'pv.id');
                });
        });
    }

    private function applyStockFilter(\Illuminate\Database\Query\Builder $query, array $criteria): void
    {
        $status = $criteria['stock_status'];

        if ($status === null || $status === '') {
            return;
        }

        if ($status === 'in_stock') {
            $query->whereRaw('COALESCE(ss.quantity_available, 0) > 0');

            return;
        }

        if ($status === 'out_of_stock') {
            $query->whereRaw('COALESCE(ss.quantity_available, 0) <= 0');

            return;
        }

        if ($status === 'low_stock') {
            $threshold = (string) $criteria['low_stock_threshold'];
            $query->whereRaw('COALESCE(ss.quantity_available, 0) > 0')
                ->whereRaw('COALESCE(ss.quantity_available, 0) <= CAST(? AS DECIMAL(20,6))', [$threshold]);
        }
    }

    private function applySort(\Illuminate\Database\Query\Builder $query, array $criteria): void
    {
        $sort = (string) $criteria['sort'];
        $parts = explode(':', $sort, 2);

        $column = $parts[0] ?? 'name';
        $direction = strtolower($parts[1] ?? 'asc');
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        if ($column === 'sku') {
            $query->orderByRaw('COALESCE(pv.sku, p.sku) '.$direction);

            return;
        }

        if ($column === 'available_quantity') {
            $query->orderBy('available_quantity', $direction)
                ->orderBy('product_name', 'asc');

            return;
        }

        if ($column === 'updated_at') {
            $query->orderBy('p.updated_at', $direction)
                ->orderBy('product_name', 'asc');

            return;
        }

        $query->orderBy('product_name', $direction);
    }

    private function hydrateRelations(Paginator $paginated, int $tenantId, array $criteria): LengthAwarePaginator
    {
        $items = collect($paginated->items());

        if ($items->isEmpty()) {
            return $paginated;
        }

        $productIds = $items->pluck('product_id')->map(static fn ($id): int => (int) $id)->unique()->values();
        $variantIds = $items->pluck('variant_id')->filter(static fn ($id): bool => $id !== null)->map(static fn ($id): int => (int) $id)->unique()->values();

        $identifierLimit = (int) config('product_search.identifier_limit', 5);

        $identifiers = DB::table('product_identifiers')
            ->where('tenant_id', $tenantId)
            ->whereIn('product_id', $productIds->all())
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get(['product_id', 'variant_id', 'technology', 'format', 'value', 'is_primary'])
            ->groupBy(function ($row): string {
                $variant = $row->variant_id !== null ? (int) $row->variant_id : 0;

                return ((int) $row->product_id).':'.$variant;
            })
            ->map(static fn ($rows) => $rows->take($identifierLimit)->values()->all());

        $variantAttributes = [];
        if ($variantIds->isNotEmpty()) {
            $variantAttributes = DB::table('variant_attribute_values as vav')
                ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
                ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                ->where('vav.tenant_id', $tenantId)
                ->whereIn('vav.variant_id', $variantIds->all())
                ->whereNull('av.deleted_at')
                ->whereNull('a.deleted_at')
                ->orderBy('a.name')
                ->orderBy('av.value')
                ->get([
                    'vav.variant_id',
                    'a.id as attribute_id',
                    'a.name as attribute_name',
                    'a.type as attribute_type',
                    'av.id as attribute_value_id',
                    'av.value as attribute_value',
                ])
                ->groupBy(static fn ($row): int => (int) $row->variant_id)
                ->map(static fn ($rows) => $rows->values()->all())
                ->all();
        }

        $comboRelationships = DB::table('combo_items as ci')
            ->leftJoin('products as cp', 'cp.id', '=', 'ci.component_product_id')
            ->leftJoin('product_variants as cpv', 'cpv.id', '=', 'ci.component_variant_id')
            ->where('ci.tenant_id', $tenantId)
            ->whereIn('ci.combo_product_id', $productIds->all())
            ->whereNull('ci.deleted_at')
            ->get([
                'ci.combo_product_id as parent_product_id',
                'ci.component_product_id',
                'ci.component_variant_id',
                'ci.quantity',
                'ci.uom_id',
                'cp.name as component_product_name',
                'cp.sku as component_product_sku',
                'cpv.sku as component_variant_sku',
            ])
            ->groupBy(static fn ($row): int => (int) $row->parent_product_id)
            ->map(static fn ($rows) => $rows->values()->all());

        $pricingType = (string) ($criteria['pricing_type'] ?? 'sales');

        $hydrated = $items->map(function ($row) use ($identifiers, $variantAttributes, $comboRelationships, $pricingType): array {
            $productId = (int) $row->product_id;
            $variantId = $row->variant_id !== null ? (int) $row->variant_id : null;
            $key = $productId.':'.($variantId ?? 0);
            $contextUomId = $pricingType === 'purchase'
                ? ($row->purchase_uom_id !== null ? (int) $row->purchase_uom_id : (int) $row->base_uom_id)
                : ($row->sales_uom_id !== null ? (int) $row->sales_uom_id : (int) $row->base_uom_id);

            return [
                'product_id' => $productId,
                'name' => (string) $row->product_name,
                'slug' => (string) $row->product_slug,
                'sku' => $variantId !== null && $row->variant_sku !== null ? (string) $row->variant_sku : ($row->product_sku !== null ? (string) $row->product_sku : null),
                'type' => (string) $row->product_type,
                'is_active' => (bool) $row->is_active,
                'category' => [
                    'id' => $row->category_id !== null ? (int) $row->category_id : null,
                    'name' => $row->category_name,
                ],
                'brand' => [
                    'id' => $row->brand_id !== null ? (int) $row->brand_id : null,
                    'name' => $row->brand_name,
                ],
                'variant' => [
                    'id' => $variantId,
                    'name' => $row->variant_name,
                    'sku' => $row->variant_sku,
                    'is_default' => (bool) ($row->variant_is_default ?? false),
                ],
                'uom' => [
                    'base' => [
                        'id' => (int) $row->base_uom_id,
                        'name' => $row->base_uom_name,
                        'symbol' => $row->base_uom_symbol,
                    ],
                    'purchase' => [
                        'id' => $row->purchase_uom_id !== null ? (int) $row->purchase_uom_id : null,
                        'name' => $row->purchase_uom_name,
                        'symbol' => $row->purchase_uom_symbol,
                    ],
                    'sales' => [
                        'id' => $row->sales_uom_id !== null ? (int) $row->sales_uom_id : null,
                        'name' => $row->sales_uom_name,
                        'symbol' => $row->sales_uom_symbol,
                    ],
                ],
                'context_uom_id' => $contextUomId,
                'quantity_on_hand' => number_format((float) $row->quantity_on_hand, 6, '.', ''),
                'quantity_reserved' => number_format((float) $row->quantity_reserved, 6, '.', ''),
                'available_quantity' => number_format((float) $row->available_quantity, 6, '.', ''),
                'unit_cost' => number_format((float) $row->unit_cost, 6, '.', ''),
                'identifiers' => $identifiers->get($key, []),
                'variant_attributes' => $variantId !== null ? ($variantAttributes[$variantId] ?? []) : [],
                'relationships' => [
                    'combo_components' => $comboRelationships->get($productId, []),
                ],
                'metadata' => $row->product_metadata !== null ? json_decode((string) $row->product_metadata, true) : null,
            ];
        })->values()->all();

        return new Paginator(
            items: $hydrated,
            total: $paginated->total(),
            perPage: $paginated->perPage(),
            currentPage: $paginated->currentPage(),
            options: ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
