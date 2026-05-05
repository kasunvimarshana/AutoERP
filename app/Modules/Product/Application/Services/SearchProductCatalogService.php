<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;

class SearchProductCatalogService implements SearchProductCatalogServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function execute(array $data = []): array
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $query = isset($data['q']) ? trim((string) $data['q']) : '';
        $variantAttribute = isset($data['variant_attribute']) ? trim((string) $data['variant_attribute']) : '';
        $warehouseId = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
        $pricingType = isset($data['pricing_type']) ? (string) $data['pricing_type'] : null;
        $currencyId = isset($data['currency_id']) ? (int) $data['currency_id'] : null;
        $quantity = isset($data['quantity']) ? (string) number_format((float) $data['quantity'], 6, '.', '') : '1.000000';
        $customerId = isset($data['customer_id']) ? (int) $data['customer_id'] : null;
        $supplierId = isset($data['supplier_id']) ? (int) $data['supplier_id'] : null;
        $stockStatus = isset($data['stock_status']) ? (string) $data['stock_status'] : 'all';
        $includeInactive = (bool) ($data['include_inactive'] ?? false);
        $perPage = (int) ($data['per_page'] ?? 20);
        $page = (int) ($data['page'] ?? 1);
        $sort = (string) ($data['sort'] ?? 'name');

        $stockSubQuery = DB::table('stock_levels as sl')
            ->join('warehouse_locations as wl', 'wl.id', '=', 'sl.location_id')
            ->where('sl.tenant_id', $tenantId)
            ->when($warehouseId !== null, fn ($builder) => $builder->where('wl.warehouse_id', $warehouseId))
            ->groupBy('sl.product_id', 'sl.variant_id')
            ->selectRaw('sl.product_id, sl.variant_id, SUM(sl.quantity_on_hand) as quantity_on_hand, SUM(sl.quantity_reserved) as quantity_reserved, SUM(sl.quantity_available) as quantity_available');

        $today = now()->toDateString();

        $priceCandidates = $this->buildPriceCandidatesQuery(
            tenantId: $tenantId,
            pricingType: $pricingType,
            currencyId: $currencyId,
            customerId: $customerId,
            supplierId: $supplierId,
            quantity: $quantity,
            today: $today,
        )
            ->select([
                'pli.product_id',
                'pli.variant_id',
                'pli.price as base_price',
                DB::raw('(pli.price * (1 - (pli.discount_pct / 100))) as unit_price'),
                DB::raw('COALESCE(cpl.priority, spl.priority, 0) as assignment_priority'),
                DB::raw('CASE WHEN cpl.id IS NOT NULL OR spl.id IS NOT NULL THEN 1 ELSE 0 END as assigned_match'),
                DB::raw('CASE WHEN pli.variant_id IS NULL THEN 0 ELSE 1 END as variant_specificity'),
                'pli.min_quantity',
                'pli.id as price_list_item_id',
                'pl.is_default',
            ]);

        $rankedPriceCandidates = DB::query()
            ->fromSub($priceCandidates, 'pc')
            ->selectRaw(
                'pc.product_id, pc.variant_id, pc.base_price, pc.unit_price, ROW_NUMBER() OVER (PARTITION BY pc.product_id, pc.variant_id ORDER BY pc.assigned_match DESC, pc.assignment_priority DESC, pc.variant_specificity DESC, pc.min_quantity DESC, pc.is_default DESC, pc.price_list_item_id ASC) as rn'
            );

        $priceSubQuery = DB::query()
            ->fromSub($rankedPriceCandidates, 'ranked_price')
            ->where('ranked_price.rn', 1)
            ->select([
                'ranked_price.product_id',
                'ranked_price.variant_id',
                'ranked_price.base_price',
                'ranked_price.unit_price',
            ]);

        $searchQuery = DB::table('products as p')
            ->leftJoin('product_variants as pv', function ($join): void {
                $join->on('pv.product_id', '=', 'p.id')
                    ->on('pv.tenant_id', '=', 'p.tenant_id')
                    ->whereNull('pv.deleted_at')
                    ->where('pv.is_active', '=', true);
            })
            ->leftJoin('units_of_measure as uom', function ($join): void {
                $join->on('uom.id', '=', 'p.base_uom_id')
                    ->on('uom.tenant_id', '=', 'p.tenant_id')
                    ->whereNull('uom.deleted_at');
            })
            ->leftJoinSub($stockSubQuery, 'stock', function ($join): void {
                $join->on('stock.product_id', '=', 'p.id')
                    ->where(function ($q): void {
                        $q->whereColumn('stock.variant_id', 'pv.id')
                            ->orWhere(function ($q2): void {
                                $q2->whereNull('stock.variant_id')
                                    ->whereNull('pv.id');
                            });
                    });
            })
            ->leftJoinSub($priceSubQuery, 'price', function ($join): void {
                $join->on('price.product_id', '=', 'p.id')
                    ->where(function ($q): void {
                        $q->whereColumn('price.variant_id', 'pv.id')
                            ->orWhere(function ($q2): void {
                                $q2->whereNull('price.variant_id')
                                    ->whereNull('pv.id');
                            });
                    });
            })
            ->where('p.tenant_id', $tenantId)
            ->whereNull('p.deleted_at')
            ->when(! $includeInactive, fn ($builder) => $builder->where('p.is_active', true))
            ->when(isset($data['category_id']), fn ($builder) => $builder->where('p.category_id', (int) $data['category_id']))
            ->when(isset($data['brand_id']), fn ($builder) => $builder->where('p.brand_id', (int) $data['brand_id']))
            ->when($stockStatus === 'in_stock', fn ($builder) => $builder->whereRaw('COALESCE(stock.quantity_available, 0) > 0'))
            ->when($stockStatus === 'out_of_stock', fn ($builder) => $builder->whereRaw('COALESCE(stock.quantity_available, 0) <= 0'))
            ->when($query !== '', function ($builder) use ($query, $tenantId): void {
                $like = '%'.$query.'%';

                $builder->where(function ($search) use ($like, $tenantId): void {
                    $search->where('p.name', 'like', $like)
                        ->orWhere('p.sku', 'like', $like)
                        ->orWhere('pv.name', 'like', $like)
                        ->orWhere('pv.sku', 'like', $like)
                        ->orWhereExists(function ($exists) use ($tenantId, $like): void {
                            $exists->selectRaw('1')
                                ->from('product_identifiers as pi')
                                ->whereColumn('pi.product_id', 'p.id')
                                ->where('pi.tenant_id', $tenantId)
                                ->where('pi.is_active', true)
                                ->where('pi.value', 'like', $like)
                                ->where(function ($q): void {
                                    $q->whereNull('pi.variant_id')
                                        ->orWhereColumn('pi.variant_id', 'pv.id');
                                });
                        })
                        ->orWhereExists(function ($exists) use ($tenantId, $like): void {
                            $exists->selectRaw('1')
                                ->from('batches as b')
                                ->whereColumn('b.product_id', 'p.id')
                                ->where('b.tenant_id', $tenantId)
                                ->where(function ($q): void {
                                    $q->whereNull('b.variant_id')
                                        ->orWhereColumn('b.variant_id', 'pv.id');
                                })
                                ->where(function ($q) use ($like): void {
                                    $q->where('b.batch_number', 'like', $like)
                                        ->orWhere('b.lot_number', 'like', $like);
                                });
                        })
                        ->orWhereExists(function ($exists) use ($like): void {
                            $exists->selectRaw('1')
                                ->from('variant_attribute_values as vav')
                                ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
                                ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                                ->whereColumn('vav.variant_id', 'pv.id')
                                ->where(function ($q) use ($like): void {
                                    $q->where('av.value', 'like', $like)
                                        ->orWhere('a.name', 'like', $like);
                                });
                        });
                });
            })
            ->when($variantAttribute !== '', function ($builder) use ($variantAttribute): void {
                $like = '%'.$variantAttribute.'%';

                $builder->whereExists(function ($exists) use ($like): void {
                    $exists->selectRaw('1')
                        ->from('variant_attribute_values as vav')
                        ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
                        ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                        ->whereColumn('vav.variant_id', 'pv.id')
                        ->where(function ($q) use ($like): void {
                            $q->where('av.value', 'like', $like)
                                ->orWhere('a.name', 'like', $like);
                        });
                });
            })
            ->select([
                'p.id as product_id',
                'p.name as product_name',
                'p.type as product_type',
                'p.sku as product_sku',
                'p.category_id',
                'p.brand_id',
                'p.is_active as product_is_active',
                'p.purchase_price as product_purchase_price',
                'p.sales_price as product_sales_price',
                'pv.id as variant_id',
                'pv.name as variant_name',
                'pv.sku as variant_sku',
                'pv.is_default as variant_is_default',
                'pv.purchase_price as variant_purchase_price',
                'pv.sales_price as variant_sales_price',
                'uom.id as uom_id',
                'uom.name as uom_name',
                'uom.symbol as uom_symbol',
                DB::raw('COALESCE(stock.quantity_on_hand, 0) as quantity_on_hand'),
                DB::raw('COALESCE(stock.quantity_reserved, 0) as quantity_reserved'),
                DB::raw('COALESCE(stock.quantity_available, 0) as quantity_available'),
                DB::raw('price.base_price as context_base_price'),
                DB::raw('price.unit_price as context_unit_price'),
            ])
            ->distinct();

        if ($sort === 'sku') {
            $searchQuery->orderBy('p.sku')->orderBy('pv.sku');
        } elseif ($sort === '-name') {
            $searchQuery->orderByDesc('p.name')->orderByDesc('pv.name');
        } elseif ($sort === '-sku') {
            $searchQuery->orderByDesc('p.sku')->orderByDesc('pv.sku');
        } else {
            $searchQuery->orderBy('p.name')->orderByDesc('pv.is_default')->orderBy('pv.name');
        }

        $paginator = $searchQuery->paginate($perPage, ['*'], 'page', $page);

        $rows = collect($paginator->items());
        $productIds = $rows->pluck('product_id')->filter()->unique()->values();
        $variantIds = $rows->pluck('variant_id')->filter()->unique()->values();

        $identifiers = $this->loadIdentifiers($tenantId, $productIds, $variantIds);
        $variantAttributes = $this->loadVariantAttributes($variantIds);
        $comboRelationships = $this->loadComboRelationships($tenantId, $productIds);
        $genericContextPrices = $this->loadGenericContextPrices(
            tenantId: $tenantId,
            productIds: $productIds,
            pricingType: $pricingType,
            currencyId: $currencyId,
            customerId: $customerId,
            supplierId: $supplierId,
            quantity: $quantity,
            today: $today,
        );

        $items = $rows->map(function (object $row) use ($identifiers, $variantAttributes, $comboRelationships, $pricingType, $currencyId, $customerId, $supplierId, $quantity, $genericContextPrices): array {
            $itemKey = $this->buildKey((int) $row->product_id, $row->variant_id !== null ? (int) $row->variant_id : null);

            $defaultUnitPrice = $this->resolveDefaultPrice($row, $pricingType);
            $contextUnitPrice = $row->context_unit_price !== null ? (string) number_format((float) $row->context_unit_price, 6, '.', '') : null;
            $contextBasePrice = $row->context_base_price !== null ? (string) number_format((float) $row->context_base_price, 6, '.', '') : null;

            if ($row->variant_id !== null && $contextUnitPrice === null) {
                $generic = $genericContextPrices->get((int) $row->product_id);
                if (is_array($generic)) {
                    $contextBasePrice = $generic['base_price'];
                    $contextUnitPrice = $generic['unit_price'];
                }
            }

            return [
                'product_id' => (int) $row->product_id,
                'variant_id' => $row->variant_id !== null ? (int) $row->variant_id : null,
                'name' => (string) $row->product_name,
                'sku' => $row->variant_sku ?? $row->product_sku,
                'product' => [
                    'id' => (int) $row->product_id,
                    'name' => (string) $row->product_name,
                    'sku' => $row->product_sku,
                    'type' => (string) $row->product_type,
                    'category_id' => $row->category_id !== null ? (int) $row->category_id : null,
                    'brand_id' => $row->brand_id !== null ? (int) $row->brand_id : null,
                    'is_active' => (bool) $row->product_is_active,
                ],
                'variant' => $row->variant_id !== null ? [
                    'id' => (int) $row->variant_id,
                    'name' => $row->variant_name,
                    'sku' => $row->variant_sku,
                    'is_default' => (bool) $row->variant_is_default,
                ] : null,
                'identifiers' => $identifiers->get($itemKey, []),
                'variant_attributes' => $row->variant_id !== null ? $variantAttributes->get((int) $row->variant_id, []) : [],
                'uom' => [
                    'id' => $row->uom_id !== null ? (int) $row->uom_id : null,
                    'name' => $row->uom_name,
                    'symbol' => $row->uom_symbol,
                ],
                'pricing' => [
                    'context_type' => $pricingType,
                    'currency_id' => $currencyId,
                    'quantity' => $quantity,
                    'customer_id' => $customerId,
                    'supplier_id' => $supplierId,
                    'base_price' => $contextBasePrice,
                    'unit_price' => $contextUnitPrice ?? $defaultUnitPrice,
                    'fallback_price' => $defaultUnitPrice,
                ],
                'quantity' => [
                    'on_hand' => (string) number_format((float) $row->quantity_on_hand, 6, '.', ''),
                    'reserved' => (string) number_format((float) $row->quantity_reserved, 6, '.', ''),
                    'available' => (string) number_format((float) $row->quantity_available, 6, '.', ''),
                ],
                'relationships' => $comboRelationships->get((int) $row->product_id, [
                    'is_combo' => false,
                    'component_count' => 0,
                    'used_in_combo_count' => 0,
                ]),
            ];
        })->values()->all();

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    private function resolveDefaultPrice(object $row, ?string $pricingType): ?string
    {
        if ($pricingType === 'purchase') {
            $price = $row->variant_purchase_price ?? $row->product_purchase_price;

            return $price !== null ? (string) number_format((float) $price, 6, '.', '') : null;
        }

        $price = $row->variant_sales_price ?? $row->product_sales_price;

        return $price !== null ? (string) number_format((float) $price, 6, '.', '') : null;
    }

    /**
     * @param  Collection<int, int>  $productIds
     */
    private function loadGenericContextPrices(
        int $tenantId,
        Collection $productIds,
        ?string $pricingType,
        ?int $currencyId,
        ?int $customerId,
        ?int $supplierId,
        string $quantity,
        string $today,
    ): Collection {
        if ($productIds->isEmpty() || $pricingType === null || $currencyId === null) {
            return collect();
        }

        $candidates = $this->buildPriceCandidatesQuery(
            tenantId: $tenantId,
            pricingType: $pricingType,
            currencyId: $currencyId,
            customerId: $customerId,
            supplierId: $supplierId,
            quantity: $quantity,
            today: $today,
            productIds: $productIds,
            onlyGenericVariant: true,
        )
            ->select([
                'pli.product_id',
                'pli.price as base_price',
                DB::raw('(pli.price * (1 - (pli.discount_pct / 100))) as unit_price'),
                DB::raw('COALESCE(cpl.priority, spl.priority, 0) as assignment_priority'),
                DB::raw('CASE WHEN cpl.id IS NOT NULL OR spl.id IS NOT NULL THEN 1 ELSE 0 END as assigned_match'),
                'pli.min_quantity',
                'pli.id as price_list_item_id',
                'pl.is_default',
            ]);

        $ranked = DB::query()
            ->fromSub($candidates, 'pc')
            ->selectRaw(
                'pc.product_id, pc.base_price, pc.unit_price, ROW_NUMBER() OVER (PARTITION BY pc.product_id ORDER BY pc.assigned_match DESC, pc.assignment_priority DESC, pc.min_quantity DESC, pc.is_default DESC, pc.price_list_item_id ASC) as rn'
            );

        $winnerRows = DB::query()
            ->fromSub($ranked, 'r')
            ->where('r.rn', 1)
            ->get(['r.product_id', 'r.base_price', 'r.unit_price']);

        return $winnerRows->mapWithKeys(static function (object $row): array {
            return [
                (int) $row->product_id => [
                    'base_price' => $row->base_price !== null ? (string) number_format((float) $row->base_price, 6, '.', '') : null,
                    'unit_price' => $row->unit_price !== null ? (string) number_format((float) $row->unit_price, 6, '.', '') : null,
                ],
            ];
        });
    }

    /**
     * @param  Collection<int, int>  $productIds
     * @param  Collection<int, int>  $variantIds
     */
    private function loadIdentifiers(int $tenantId, Collection $productIds, Collection $variantIds): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        $rows = DB::table('product_identifiers')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereIn('product_id', $productIds->all())
            ->where(function ($query) use ($variantIds): void {
                $query->whereNull('variant_id');

                if (! $variantIds->isEmpty()) {
                    $query->orWhereIn('variant_id', $variantIds->all());
                }
            })
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get(['product_id', 'variant_id', 'technology', 'value']);

        return $rows->groupBy(function (object $row): string {
            return $this->buildKey((int) $row->product_id, $row->variant_id !== null ? (int) $row->variant_id : null);
        })->map(fn (Collection $group): array => $group
            ->take(5)
            ->map(static fn (object $row): array => [
                'technology' => (string) $row->technology,
                'value' => (string) $row->value,
            ])
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, int>  $variantIds
     */
    private function loadVariantAttributes(Collection $variantIds): Collection
    {
        if ($variantIds->isEmpty()) {
            return collect();
        }

        $rows = DB::table('variant_attribute_values as vav')
            ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
            ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
            ->whereIn('vav.variant_id', $variantIds->all())
            ->orderBy('a.name')
            ->orderBy('av.value')
            ->get(['vav.variant_id', 'a.name as attribute_name', 'av.value as attribute_value']);

        return $rows->groupBy(fn (object $row): int => (int) $row->variant_id)
            ->map(fn (Collection $group): array => $group
                ->map(static fn (object $row): array => [
                    'name' => (string) $row->attribute_name,
                    'value' => (string) $row->attribute_value,
                ])
                ->values()
                ->all());
    }

    /**
     * @param  Collection<int, int>  $productIds
     */
    private function loadComboRelationships(int $tenantId, Collection $productIds): Collection
    {
        if ($productIds->isEmpty()) {
            return collect();
        }

        $asCombo = DB::table('combo_items')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereIn('combo_product_id', $productIds->all())
            ->selectRaw('combo_product_id as product_id, COUNT(*) as component_count')
            ->groupBy('combo_product_id')
            ->pluck('component_count', 'product_id');

        $asComponent = DB::table('combo_items')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereIn('component_product_id', $productIds->all())
            ->selectRaw('component_product_id as product_id, COUNT(*) as used_in_combo_count')
            ->groupBy('component_product_id')
            ->pluck('used_in_combo_count', 'product_id');

        return $productIds->mapWithKeys(function (int $productId) use ($asCombo, $asComponent): array {
            $componentCount = (int) ($asCombo[$productId] ?? 0);
            $usedInComboCount = (int) ($asComponent[$productId] ?? 0);

            return [
                $productId => [
                    'is_combo' => $componentCount > 0,
                    'component_count' => $componentCount,
                    'used_in_combo_count' => $usedInComboCount,
                ],
            ];
        });
    }

    private function buildKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId !== null ? (string) $variantId : 'null');
    }

    /**
     * @param  Collection<int, int>|null  $productIds
     */
    private function buildPriceCandidatesQuery(
        int $tenantId,
        ?string $pricingType,
        ?int $currencyId,
        ?int $customerId,
        ?int $supplierId,
        string $quantity,
        string $today,
        ?Collection $productIds = null,
        bool $onlyGenericVariant = false,
    ): mixed {
        $query = DB::table('price_list_items as pli')
            ->join('price_lists as pl', function ($join) use ($tenantId): void {
                $join->on('pl.id', '=', 'pli.price_list_id')
                    ->where('pl.tenant_id', '=', $tenantId)
                    ->where('pl.is_active', '=', true);
            })
            ->leftJoin('customer_price_lists as cpl', function ($join) use ($tenantId, $customerId): void {
                $join->on('cpl.price_list_id', '=', 'pl.id')
                    ->where('cpl.tenant_id', '=', $tenantId);

                if ($customerId !== null) {
                    $join->where('cpl.customer_id', '=', $customerId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin('supplier_price_lists as spl', function ($join) use ($tenantId, $supplierId): void {
                $join->on('spl.price_list_id', '=', 'pl.id')
                    ->where('spl.tenant_id', '=', $tenantId);

                if ($supplierId !== null) {
                    $join->where('spl.supplier_id', '=', $supplierId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->where('pli.tenant_id', $tenantId)
            ->when($pricingType !== null, fn ($builder) => $builder->where('pl.type', $pricingType))
            ->when($currencyId !== null, fn ($builder) => $builder->where('pl.currency_id', $currencyId))
            ->whereRaw('CAST(pli.min_quantity AS DECIMAL(20,6)) <= CAST(? AS DECIMAL(20,6))', [$quantity])
            ->where(function ($q) use ($today): void {
                $q->whereNull('pl.valid_from')->orWhereDate('pl.valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today): void {
                $q->whereNull('pl.valid_to')->orWhereDate('pl.valid_to', '>=', $today);
            })
            ->when($pricingType === 'sales', function ($builder) use ($customerId): void {
                if ($customerId !== null) {
                    $builder->where(function ($q): void {
                        $q->whereNotNull('cpl.id')
                            ->orWhere('pl.is_default', true);
                    });
                } else {
                    $builder->where('pl.is_default', true);
                }
            })
            ->when($pricingType === 'purchase', function ($builder) use ($supplierId): void {
                if ($supplierId !== null) {
                    $builder->where(function ($q): void {
                        $q->whereNotNull('spl.id')
                            ->orWhere('pl.is_default', true);
                    });
                } else {
                    $builder->where('pl.is_default', true);
                }
            });

        if ($productIds !== null && ! $productIds->isEmpty()) {
            $query->whereIn('pli.product_id', $productIds->all());
        }

        if ($onlyGenericVariant) {
            $query->whereNull('pli.variant_id');
        }

        return $query;
    }
}
