<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Product\Application\Contracts\SearchProductsServiceInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductSearchProjectionRepositoryInterface;

class SearchProductsService implements SearchProductsServiceInterface
{
    public function __construct(
        private readonly ProductSearchProjectionRepositoryInterface $projectionRepository,
        private readonly ResolvePriceServiceInterface $resolvePriceService,
    ) {}

    public function execute(array $filters = []): LengthAwarePaginator
    {
        $results = $this->projectionRepository->search($filters);

        $includePricing = (bool) ($filters['include_pricing'] ?? false);
        if (! $includePricing) {
            return $results;
        }

        $contextType = (string) ($filters['context_type'] ?? '');
        $currencyId = isset($filters['currency_id']) ? (int) $filters['currency_id'] : null;
        if (! in_array($contextType, ['purchase', 'sales'], true) || $currencyId === null) {
            return $results;
        }

        $tenantId = (int) ($filters['tenant_id'] ?? 0);
        $quantity = isset($filters['price_quantity']) ? (string) $filters['price_quantity'] : '1.000000';
        $uomIdOverride = isset($filters['price_uom_id']) ? (int) $filters['price_uom_id'] : null;

        $items = $results->getCollection()->map(function (object $row) use (
            $contextType,
            $currencyId,
            $filters,
            $quantity,
            $tenantId,
            $uomIdOverride
        ): object {
            $uomId = $uomIdOverride;
            if ($uomId === null) {
                $uomId = $contextType === 'purchase'
                    ? ((int) ($row->purchase_uom_id ?? 0) ?: (int) ($row->base_uom_id ?? 0))
                    : ((int) ($row->sales_uom_id ?? 0) ?: (int) ($row->base_uom_id ?? 0));
            }

            $priceData = null;
            if ($uomId > 0) {
                try {
                    $priceData = $this->resolvePriceService->execute([
                        'tenant_id' => $tenantId,
                        'type' => $contextType,
                        'product_id' => (int) $row->product_id,
                        'variant_id' => $row->variant_id !== null ? (int) $row->variant_id : null,
                        'uom_id' => $uomId,
                        'quantity' => $quantity,
                        'currency_id' => $currencyId,
                        'customer_id' => isset($filters['customer_id']) ? (int) $filters['customer_id'] : null,
                        'supplier_id' => isset($filters['supplier_id']) ? (int) $filters['supplier_id'] : null,
                        'price_date' => $filters['price_date'] ?? null,
                    ]);
                } catch (\Throwable) {
                    $priceData = null;
                }
            }

            $row->resolved_price = $priceData;

            return $row;
        });

        $results->setCollection($items);

        return $results;
    }
}
