<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchProjectionRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductSearchProjectionModel;

class EloquentProductSearchProjectionRepository implements ProductSearchProjectionRepositoryInterface
{
    public function __construct(
        private readonly ProductSearchProjectionModel $projectionModel,
    ) {}

    public function rebuildForTenant(int $tenantId): int
    {
        return $this->rebuild($tenantId, null);
    }

    public function rebuildForProduct(int $tenantId, int $productId): int
    {
        return $this->rebuild($tenantId, $productId);
    }

    private function rebuild(int $tenantId, ?int $productId): int
    {
        $now = now();

        $baseRowsQuery = DB::table('products as p')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('product_brands as b', 'b.id', '=', 'p.brand_id')
            ->where('p.tenant_id', $tenantId)
            ->whereNull('p.deleted_at')
            ->select([
                'p.id as product_id',
                DB::raw('NULL as variant_id'),
                DB::raw('0 as variant_key'),
                'p.name as product_name',
                'p.slug as product_slug',
                'p.sku as product_sku',
                DB::raw('NULL as variant_name'),
                DB::raw('NULL as variant_sku'),
                'p.category_id',
                'c.name as category_name',
                'p.brand_id',
                'b.name as brand_name',
                'p.base_uom_id',
                'p.purchase_uom_id',
                'p.sales_uom_id',
                'p.is_active as is_active_product',
                DB::raw('1 as is_active_variant'),
                'p.updated_at as source_updated_at',
            ]);

        if ($productId !== null) {
            $baseRowsQuery->where('p.id', $productId);
        }

        $baseRows = $baseRowsQuery->get();

        $variantRowsQuery = DB::table('products as p')
            ->join('product_variants as pv', 'pv.product_id', '=', 'p.id')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('product_brands as b', 'b.id', '=', 'p.brand_id')
            ->where('p.tenant_id', $tenantId)
            ->whereNull('p.deleted_at')
            ->whereNull('pv.deleted_at')
            ->select([
                'p.id as product_id',
                'pv.id as variant_id',
                DB::raw('pv.id as variant_key'),
                'p.name as product_name',
                'p.slug as product_slug',
                'p.sku as product_sku',
                'pv.name as variant_name',
                'pv.sku as variant_sku',
                'p.category_id',
                'c.name as category_name',
                'p.brand_id',
                'b.name as brand_name',
                'p.base_uom_id',
                'p.purchase_uom_id',
                'p.sales_uom_id',
                'p.is_active as is_active_product',
                'pv.is_active as is_active_variant',
                DB::raw('COALESCE(pv.updated_at, p.updated_at) as source_updated_at'),
            ]);

        if ($productId !== null) {
            $variantRowsQuery->where('p.id', $productId);
        }

        $variantRows = $variantRowsQuery->get();

        $rows = $baseRows->concat($variantRows);

        $identifierMap = $this->buildIdentifierMap($tenantId);
        $attributeMap = $this->buildVariantAttributeMap($tenantId);
        $batchLotMap = $this->buildBatchLotMap($tenantId);
        [$stockExactMap, $stockByProductMap, $stockWarehouseExactMap, $stockWarehouseByProductMap] = $this->buildStockMaps($tenantId);

        $payload = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $variantId = $row->variant_id !== null ? (int) $row->variant_id : null;
            $variantKey = $variantId ?? 0;

            $identifierData = $this->resolveIdentifiers($identifierMap, $productId, $variantId);
            $attributeData = $variantId !== null ? ($attributeMap[$variantId] ?? []) : [];
            $batchLotText = $this->resolveBatchLotText($batchLotMap, $productId, $variantId);

            $stock = $variantId !== null
                ? ($stockExactMap[$this->stockKey($productId, $variantId)] ?? ['on_hand' => '0.000000', 'reserved' => '0.000000', 'available' => '0.000000'])
                : ($stockByProductMap[$productId] ?? ['on_hand' => '0.000000', 'reserved' => '0.000000', 'available' => '0.000000']);

            $stockByWarehouse = $variantId !== null
                ? ($stockWarehouseExactMap[$this->stockKey($productId, $variantId)] ?? [])
                : ($stockWarehouseByProductMap[$productId] ?? []);

            $searchableText = trim(implode(' ', array_filter([
                (string) $row->product_name,
                (string) ($row->product_slug ?? ''),
                (string) ($row->product_sku ?? ''),
                (string) ($row->variant_name ?? ''),
                (string) ($row->variant_sku ?? ''),
                implode(' ', $identifierData),
                implode(' ', array_map(static fn (array $item): string => ($item['attribute'] ?? '').' '.($item['value'] ?? ''), $attributeData)),
                $batchLotText,
                (string) ($row->category_name ?? ''),
                (string) ($row->brand_name ?? ''),
            ])));

            $payload[] = [
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'variant_key' => $variantKey,
                'product_name' => (string) $row->product_name,
                'product_slug' => (string) $row->product_slug,
                'product_sku' => $row->product_sku,
                'variant_name' => $row->variant_name,
                'variant_sku' => $row->variant_sku,
                'category_id' => $row->category_id,
                'category_name' => $row->category_name,
                'brand_id' => $row->brand_id,
                'brand_name' => $row->brand_name,
                'base_uom_id' => (int) $row->base_uom_id,
                'purchase_uom_id' => $row->purchase_uom_id,
                'sales_uom_id' => $row->sales_uom_id,
                'is_active_product' => (bool) $row->is_active_product,
                'is_active_variant' => (bool) $row->is_active_variant,
                'identifiers_text' => implode(' ', $identifierData),
                'identifiers_json' => $this->toJson($identifierData),
                'variant_attributes_json' => $this->toJson($attributeData),
                'batch_lot_text' => $batchLotText,
                'stock_on_hand' => $stock['on_hand'],
                'stock_reserved' => $stock['reserved'],
                'stock_available' => $stock['available'],
                'stock_by_warehouse_json' => $this->toJson($stockByWarehouse),
                'searchable_text' => $searchableText,
                'source_updated_at' => $row->source_updated_at,
                'last_projected_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($tenantId, $productId, $payload): void {
            $deleteQuery = $this->projectionModel->newQuery()->where('tenant_id', $tenantId);
            if ($productId !== null) {
                $deleteQuery->where('product_id', $productId);
            }

            $deleteQuery->forceDelete();

            foreach (array_chunk($payload, 500) as $chunk) {
                $this->projectionModel->newQuery()->insert($chunk);
            }
        });

        return count($payload);
    }

    public function search(array $filters = []): LengthAwarePaginator
    {
        $tenantId = (int) ($filters['tenant_id'] ?? 0);

        $query = $this->projectionModel->newQuery()->where('tenant_id', $tenantId);

        if (array_key_exists('is_active', $filters)) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active_product', $isActive);
            }
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['brand_id'])) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (array_key_exists('variant_id', $filters)) {
            $variantId = $filters['variant_id'];
            if ($variantId === null || $variantId === '') {
                $query->whereNull('variant_id');
            } else {
                $query->where('variant_id', (int) $variantId);
            }
        }

        if (isset($filters['sku'])) {
            $sku = trim((string) $filters['sku']);
            $query->where(function ($builder) use ($sku): void {
                $builder->where('product_sku', $sku)
                    ->orWhere('variant_sku', $sku);
            });
        }

        $keyword = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($keyword !== '') {
            foreach ($this->tokenize($keyword) as $token) {
                $query->where('searchable_text', 'like', '%'.$token.'%');
            }
        }

        $identifierQueryMap = [
            'identifier' => 'identifiers_text',
            'barcode' => 'identifiers_text',
            'rfid' => 'identifiers_text',
            'qr' => 'identifiers_text',
            'batch' => 'batch_lot_text',
            'lot' => 'batch_lot_text',
        ];

        foreach ($identifierQueryMap as $field => $column) {
            if (! isset($filters[$field])) {
                continue;
            }

            $value = trim((string) $filters[$field]);
            if ($value === '') {
                continue;
            }

            $query->where($column, 'like', '%'.$value.'%');
        }

        if (isset($filters['in_stock'])) {
            $inStock = filter_var($filters['in_stock'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($inStock === true) {
                $query->where('stock_available', '>', '0');
            }
            if ($inStock === false) {
                $query->where('stock_available', '<=', '0');
            }
        }

        if (isset($filters['min_available'])) {
            $query->where('stock_available', '>=', (string) $filters['min_available']);
        }

        if (isset($filters['max_available'])) {
            $query->where('stock_available', '<=', (string) $filters['max_available']);
        }

        if (isset($filters['warehouse_id'])) {
            $warehouseId = (int) $filters['warehouse_id'];
            $query
                ->leftJoin('stock_levels as sl', function ($join) use ($tenantId): void {
                    $join->on('sl.product_id', '=', 'product_search_projections.product_id')
                        ->where('sl.tenant_id', '=', $tenantId)
                        ->where(function ($variantJoin): void {
                            $variantJoin->whereColumn('sl.variant_id', 'product_search_projections.variant_id')
                                ->orWhere(function ($baseRow): void {
                                    $baseRow->whereNull('product_search_projections.variant_id');
                                });
                        });
                })
                ->leftJoin('warehouse_locations as wl', 'wl.id', '=', 'sl.location_id')
                ->where(function ($warehouseFilter) use ($warehouseId): void {
                    $warehouseFilter->where('wl.warehouse_id', $warehouseId)
                        ->orWhereNull('wl.warehouse_id');
                })
                ->addSelect(DB::raw('COALESCE(SUM(CASE WHEN wl.warehouse_id = '.(int) $warehouseId.' THEN sl.quantity_available ELSE 0 END), 0) as warehouse_quantity_available'))
                ->groupBy('product_search_projections.id');

            if (isset($filters['warehouse_in_stock'])) {
                $warehouseInStock = filter_var($filters['warehouse_in_stock'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if ($warehouseInStock === true) {
                    $query->havingRaw('COALESCE(SUM(CASE WHEN wl.warehouse_id = ? THEN sl.quantity_available ELSE 0 END), 0) > 0', [$warehouseId]);
                }
                if ($warehouseInStock === false) {
                    $query->havingRaw('COALESCE(SUM(CASE WHEN wl.warehouse_id = ? THEN sl.quantity_available ELSE 0 END), 0) <= 0', [$warehouseId]);
                }
            }
        }

        $sort = (string) ($filters['sort'] ?? 'relevance');
        if ($keyword !== '' && $sort === 'relevance') {
            $query
                ->orderByRaw('CASE WHEN product_sku = ? OR variant_sku = ? THEN 0 WHEN identifiers_text LIKE ? THEN 1 ELSE 2 END', [$keyword, $keyword, '%'.$keyword.'%'])
                ->orderBy('product_name')
                ->orderBy('variant_name');
        } elseif ($sort === 'name') {
            $query->orderBy('product_name')->orderBy('variant_name');
        } elseif ($sort === '-name') {
            $query->orderByDesc('product_name')->orderByDesc('variant_name');
        } elseif ($sort === 'stock') {
            $query->orderByDesc('stock_available');
        } elseif ($sort === '-stock') {
            $query->orderBy('stock_available');
        } else {
            $query->orderByDesc('source_updated_at')->orderByDesc('id');
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        return $query->paginate($perPage, ['product_search_projections.*'], 'page', $page);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $value): array
    {
        $tokens = preg_split('/\s+/', trim($value)) ?: [];

        return array_values(array_filter(array_map(static fn (string $token): string => trim($token), $tokens)));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildIdentifierMap(int $tenantId): array
    {
        $rows = DB::table('product_identifiers')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select(['product_id', 'variant_id', 'technology', 'format', 'value'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $variantId = $row->variant_id !== null ? (int) $row->variant_id : null;
            $key = $this->stockKey($productId, $variantId);

            $parts = array_filter([
                (string) ($row->technology ?? ''),
                (string) ($row->format ?? ''),
                (string) ($row->value ?? ''),
            ]);

            $map[$key][] = implode(':', $parts);
        }

        return $map;
    }

    /**
     * @return array<int, array<int, array{attribute: string, value: string}>>
     */
    private function buildVariantAttributeMap(int $tenantId): array
    {
        $rows = DB::table('variant_attribute_values as vav')
            ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
            ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
            ->where('vav.tenant_id', $tenantId)
            ->select(['vav.variant_id', 'a.name as attribute_name', 'av.value as attribute_value'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $variantId = (int) $row->variant_id;
            $map[$variantId][] = [
                'attribute' => (string) $row->attribute_name,
                'value' => (string) $row->attribute_value,
            ];
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function buildBatchLotMap(int $tenantId): array
    {
        $rows = DB::table('batches')
            ->where('tenant_id', $tenantId)
            ->select(['product_id', 'variant_id', 'batch_number', 'lot_number'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $variantId = $row->variant_id !== null ? (int) $row->variant_id : null;
            $key = $this->stockKey($productId, $variantId);

            $parts = array_filter([(string) $row->batch_number, (string) ($row->lot_number ?? '')]);
            if ($parts === []) {
                continue;
            }

            $existing = $map[$key] ?? '';
            $map[$key] = trim($existing.' '.implode(' ', $parts));
        }

        return $map;
    }

    /**
     * @return array{0: array<string, array{on_hand: string, reserved: string, available: string}>, 1: array<int, array{on_hand: string, reserved: string, available: string}>, 2: array<string, array<int, array{warehouse_id: int, on_hand: string, reserved: string, available: string}>>, 3: array<int, array<int, array{warehouse_id: int, on_hand: string, reserved: string, available: string}>>}
     */
    private function buildStockMaps(int $tenantId): array
    {
        $rows = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->select([
                'product_id',
                'variant_id',
                DB::raw('COALESCE(SUM(quantity_on_hand), 0) as on_hand'),
                DB::raw('COALESCE(SUM(quantity_reserved), 0) as reserved'),
                DB::raw('COALESCE(SUM(quantity_available), 0) as available'),
            ])
            ->groupBy('product_id', 'variant_id')
            ->get();

        $exact = [];
        $byProduct = [];

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            $variantId = $row->variant_id !== null ? (int) $row->variant_id : null;
            $key = $this->stockKey($productId, $variantId);

            $exact[$key] = [
                'on_hand' => number_format((float) $row->on_hand, 6, '.', ''),
                'reserved' => number_format((float) $row->reserved, 6, '.', ''),
                'available' => number_format((float) $row->available, 6, '.', ''),
            ];

            if (! isset($byProduct[$productId])) {
                $byProduct[$productId] = ['on_hand' => '0.000000', 'reserved' => '0.000000', 'available' => '0.000000'];
            }

            $byProduct[$productId]['on_hand'] = bcadd($byProduct[$productId]['on_hand'], (string) $exact[$key]['on_hand'], 6);
            $byProduct[$productId]['reserved'] = bcadd($byProduct[$productId]['reserved'], (string) $exact[$key]['reserved'], 6);
            $byProduct[$productId]['available'] = bcadd($byProduct[$productId]['available'], (string) $exact[$key]['available'], 6);
        }

        $warehouseRows = DB::table('stock_levels as sl')
            ->join('warehouse_locations as wl', 'wl.id', '=', 'sl.location_id')
            ->where('sl.tenant_id', $tenantId)
            ->select([
                'sl.product_id',
                'sl.variant_id',
                'wl.warehouse_id',
                DB::raw('COALESCE(SUM(sl.quantity_on_hand), 0) as on_hand'),
                DB::raw('COALESCE(SUM(sl.quantity_reserved), 0) as reserved'),
                DB::raw('COALESCE(SUM(sl.quantity_available), 0) as available'),
            ])
            ->groupBy('sl.product_id', 'sl.variant_id', 'wl.warehouse_id')
            ->get();

        $warehouseExact = [];
        $warehouseByProduct = [];
        foreach ($warehouseRows as $row) {
            $productId = (int) $row->product_id;
            $variantId = $row->variant_id !== null ? (int) $row->variant_id : null;
            $key = $this->stockKey($productId, $variantId);

            $item = [
                'warehouse_id' => (int) $row->warehouse_id,
                'on_hand' => number_format((float) $row->on_hand, 6, '.', ''),
                'reserved' => number_format((float) $row->reserved, 6, '.', ''),
                'available' => number_format((float) $row->available, 6, '.', ''),
            ];

            $warehouseExact[$key][] = $item;
            $warehouseByProduct[$productId][] = $item;
        }

        return [$exact, $byProduct, $warehouseExact, $warehouseByProduct];
    }

    /**
     * @param  array<string, array<int, string>>  $identifierMap
     * @return array<int, string>
     */
    private function resolveIdentifiers(array $identifierMap, int $productId, ?int $variantId): array
    {
        $base = $identifierMap[$this->stockKey($productId, null)] ?? [];
        if ($variantId === null) {
            return array_values(array_unique($base));
        }

        $variant = $identifierMap[$this->stockKey($productId, $variantId)] ?? [];

        return array_values(array_unique(array_merge($base, $variant)));
    }

    /**
     * @param  array<string, string>  $batchLotMap
     */
    private function resolveBatchLotText(array $batchLotMap, int $productId, ?int $variantId): string
    {
        $base = $batchLotMap[$this->stockKey($productId, null)] ?? '';
        if ($variantId === null) {
            return trim($base);
        }

        $variant = $batchLotMap[$this->stockKey($productId, $variantId)] ?? '';

        return trim($base.' '.$variant);
    }

    private function stockKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? 0);
    }

    /**
     * @param  array<int|string, mixed>  $value
     */
    private function toJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
