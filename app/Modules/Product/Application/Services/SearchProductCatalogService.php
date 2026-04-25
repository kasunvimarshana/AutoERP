<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Product\Application\Contracts\SearchProductCatalogServiceInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchRepositoryInterface;

class SearchProductCatalogService implements SearchProductCatalogServiceInterface
{
    public function __construct(
        private readonly ProductSearchRepositoryInterface $productSearchRepository,
        private readonly PriceListItemRepositoryInterface $priceListItemRepository,
    ) {}

    public function execute(array $criteria): array
    {
        $normalized = $this->normalizeCriteria($criteria);
        $results = $this->productSearchRepository->searchCatalog($normalized);

        $data = array_map(function ($item) use ($normalized): array {
            /** @var array<string, mixed> $row */
            $row = (array) $item;

            $row['pricing'] = $this->resolvePricing($row, $normalized);
            $row['stock_status'] = $this->resolveStockStatus((string) ($row['available_quantity'] ?? '0.000000'), $normalized);

            return $row;
        }, $results->items());

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'context' => [
                'tenant_id' => $normalized['tenant_id'],
                'workflow_context' => $normalized['workflow_context'],
                'pricing_type' => $normalized['pricing_type'],
                'currency_id' => $normalized['currency_id'],
                'warehouse_id' => $normalized['warehouse_id'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function normalizeCriteria(array $criteria): array
    {
        $workflowContext = (string) ($criteria['workflow_context'] ?? 'sell');
        /** @var array<string, string> $workflowMap */
        $workflowMap = (array) config('product_search.workflow_context_pricing_map', []);

        $pricingType = (string) ($criteria['pricing_type'] ?? ($workflowMap[$workflowContext] ?? 'sales'));
        $maxPerPage = (int) config('product_search.max_per_page', 100);

        $perPage = (int) ($criteria['per_page'] ?? 15);
        $perPage = max(1, min($perPage, $maxPerPage));

        return [
            'tenant_id' => (int) $criteria['tenant_id'],
            'term' => trim((string) ($criteria['term'] ?? '')),
            'workflow_context' => $workflowContext,
            'pricing_type' => $pricingType,
            'currency_id' => isset($criteria['currency_id']) ? (int) $criteria['currency_id'] : null,
            'customer_id' => isset($criteria['customer_id']) ? (int) $criteria['customer_id'] : null,
            'supplier_id' => isset($criteria['supplier_id']) ? (int) $criteria['supplier_id'] : null,
            'warehouse_id' => isset($criteria['warehouse_id']) ? (int) $criteria['warehouse_id'] : null,
            'category_id' => isset($criteria['category_id']) ? (int) $criteria['category_id'] : null,
            'brand_id' => isset($criteria['brand_id']) ? (int) $criteria['brand_id'] : null,
            'variant_id' => isset($criteria['variant_id']) ? (int) $criteria['variant_id'] : null,
            'product_type' => $criteria['product_type'] ?? null,
            'stock_status' => $criteria['stock_status'] ?? null,
            'include_inactive' => (bool) ($criteria['include_inactive'] ?? false),
            'include_pricing' => (bool) ($criteria['include_pricing'] ?? true),
            'quantity' => isset($criteria['quantity']) ? number_format((float) $criteria['quantity'], 6, '.', '') : '1.000000',
            'price_date' => isset($criteria['price_date']) ? (string) $criteria['price_date'] : null,
            'per_page' => $perPage,
            'page' => max(1, (int) ($criteria['page'] ?? 1)),
            'sort' => (string) ($criteria['sort'] ?? 'name:asc'),
            'low_stock_threshold' => isset($criteria['low_stock_threshold'])
                ? number_format((float) $criteria['low_stock_threshold'], 6, '.', '')
                : (string) config('product_search.low_stock_threshold', '5.000000'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>|null
     */
    private function resolvePricing(array $row, array $criteria): ?array
    {
        if (! $criteria['include_pricing'] || $criteria['currency_id'] === null) {
            return null;
        }

        $contextUomId = (int) ($row['context_uom_id'] ?? $row['base_uom_id']);
        $variantId = isset($row['variant_id']) ? (int) $row['variant_id'] : null;

        $match = $this->priceListItemRepository->findBestMatch(
            tenantId: (int) $criteria['tenant_id'],
            type: (string) $criteria['pricing_type'],
            productId: (int) $row['product_id'],
            variantId: $variantId,
            uomId: $contextUomId,
            quantity: (string) $criteria['quantity'],
            currencyId: (int) $criteria['currency_id'],
            customerId: $criteria['customer_id'] !== null ? (int) $criteria['customer_id'] : null,
            supplierId: $criteria['supplier_id'] !== null ? (int) $criteria['supplier_id'] : null,
            priceDate: $criteria['price_date'] !== null
                ? new \DateTimeImmutable((string) $criteria['price_date'])
                : new \DateTimeImmutable,
        );

        if ($match === null) {
            $match = $this->resolveFallbackMatch($row, $criteria, $contextUomId);

            if ($match === null) {
                $fallbackBase = number_format((float) ($row['unit_cost'] ?? 0), 6, '.', '');
                $quantity = (float) $criteria['quantity'];

                return [
                    'price_list_id' => null,
                    'price_list_item_id' => null,
                    'uom_id' => $contextUomId,
                    'base_price' => $fallbackBase,
                    'discount_pct' => '0.000000',
                    'unit_price' => $fallbackBase,
                    'total_price' => number_format(((float) $fallbackBase) * $quantity, 6, '.', ''),
                    'quantity' => number_format($quantity, 6, '.', ''),
                ];
            }
        }

        $basePrice = (float) $match['price'];
        $discountPct = (float) $match['discount_pct'];
        $unitPrice = $basePrice * (1 - ($discountPct / 100));
        $quantity = (float) $criteria['quantity'];

        return [
            'price_list_id' => (int) $match['price_list_id'],
            'price_list_item_id' => (int) $match['id'],
            'uom_id' => $contextUomId,
            'base_price' => number_format($basePrice, 6, '.', ''),
            'discount_pct' => number_format($discountPct, 6, '.', ''),
            'unit_price' => number_format($unitPrice, 6, '.', ''),
            'total_price' => number_format($unitPrice * $quantity, 6, '.', ''),
            'quantity' => number_format($quantity, 6, '.', ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>|null
     */
    private function resolveFallbackMatch(array $row, array $criteria, int $contextUomId): ?array
    {
        $date = $criteria['price_date'] !== null
            ? (new \DateTimeImmutable((string) $criteria['price_date']))->format('Y-m-d')
            : (new \DateTimeImmutable)->format('Y-m-d');

        $variantId = isset($row['variant_id']) ? (int) $row['variant_id'] : null;

        $query = DB::table('price_list_items as pli')
            ->join('price_lists as pl', 'pl.id', '=', 'pli.price_list_id')
            ->where('pl.tenant_id', (int) $criteria['tenant_id'])
            ->where('pl.type', (string) $criteria['pricing_type'])
            ->where('pl.currency_id', (int) $criteria['currency_id'])
            ->where('pl.is_active', true)
            ->where('pli.product_id', (int) $row['product_id'])
            ->where(function ($q) use ($contextUomId): void {
                $q->where('pli.uom_id', $contextUomId)
                    ->orWhereNull('pli.uom_id');
            })
            ->where(function ($q) use ($date): void {
                $q->whereNull('pl.valid_from')->orWhereDate('pl.valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date): void {
                $q->whereNull('pl.valid_to')->orWhereDate('pl.valid_to', '>=', $date);
            })
            ->where(function ($q) use ($date): void {
                $q->whereNull('pli.valid_from')->orWhereDate('pli.valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date): void {
                $q->whereNull('pli.valid_to')->orWhereDate('pli.valid_to', '>=', $date);
            })
            ->whereRaw('CAST(pli.min_quantity AS DECIMAL(20,6)) <= CAST(? AS DECIMAL(20,6))', [(string) $criteria['quantity']]);

        if ($variantId !== null) {
            $query->where(function ($q) use ($variantId): void {
                $q->where('pli.variant_id', $variantId)->orWhereNull('pli.variant_id');
            });
        } else {
            $query->whereNull('pli.variant_id');
        }

        $match = $query
            ->select(['pli.id', 'pli.price_list_id', 'pli.price', 'pli.discount_pct', 'pli.min_quantity', 'pl.is_default'])
            ->orderByDesc('pl.is_default')
            ->orderByDesc('pli.variant_id')
            ->orderByDesc('pli.min_quantity')
            ->first();

        return $match !== null ? (array) $match : null;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function resolveStockStatus(string $availableQty, array $criteria): string
    {
        if (bccomp($availableQty, '0.000000', 6) <= 0) {
            return 'out_of_stock';
        }

        if (bccomp($availableQty, (string) $criteria['low_stock_threshold'], 6) <= 0) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
