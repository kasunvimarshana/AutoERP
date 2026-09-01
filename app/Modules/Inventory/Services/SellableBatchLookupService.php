<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\BatchStatus;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Tenant\Models\TenantModel;

final class SellableBatchLookupService
{
    public function __construct(
        private readonly BatchPriceService $batchPrices,
        private readonly ItemPriceResolutionService $itemPrices,
    ) {}

    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        string $search,
        int $perPage,
        ?int $warehouseId = null,
        ?int $warehouseLocationId = null,
    ): LengthAwarePaginator {
        $balanceScope = function (Builder $query) use ($tenantId, $organizationUnitId, $warehouseId, $warehouseLocationId): void {
            $query->where('tenant_id', $tenantId);
            $organizationUnitId === null
                ? $query->whereNull('organization_unit_id')
                : $query->where('organization_unit_id', $organizationUnitId);
            if ($warehouseId !== null) {
                $query->where('warehouse_id', $warehouseId);
            }
            if ($warehouseLocationId !== null) {
                $query->where('warehouse_location_id', $warehouseLocationId);
            }
        };

        $query = InventoryBatch::query()
            ->where('tenant_id', $tenantId)
            ->where('status', BatchStatus::Active->value)
            ->where(fn (Builder $expiry): Builder => $expiry
                ->whereNull('expiry_date')
                ->orWhereDate('expiry_date', '>=', now()->toDateString()))
            ->whereHas('item', fn (Builder $item): Builder => $item
                ->where('is_active', true)
                ->where('is_stockable', true)
                ->whereIn('tracking_type', [TrackingType::Batch->value, TrackingType::Lot->value]))
            ->with(['item.baseUom', 'variant'])
            ->withSum(['stockBalances as available_stock_quantity' => $balanceScope], 'quantity_available')
            ->whereHas('stockBalances', function (Builder $balance) use ($balanceScope): void {
                $balanceScope($balance);
                $balance->where('quantity_available', '>', 0);
            });

        if ($search !== '') {
            $query->where(function (Builder $filter) use ($search): void {
                $filter->where('batch_number', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%")
                    ->orWhereHas('item', fn (Builder $item): Builder => $item
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->orderBy('item_id')->orderBy('batch_number')->paginate(min($perPage, 50));
        $currencyId = TenantModel::query()->whereKey($tenantId)->value('base_currency_id');

        foreach ($paginator->getCollection() as $batch) {
            $item = $batch->item;
            $uomId = $item?->base_uom_id;
            $batchPrice = $currencyId === null || $uomId === null
                ? null
                : $this->batchPrices->resolve(
                    $batch,
                    ItemPriceType::Service,
                    (int) $currencyId,
                    (int) $uomId,
                    $organizationUnitId,
                );
            $fallback = $batchPrice === null && $item !== null
                ? $this->itemPrices->resolvePrice(
                    item: $item,
                    context: ItemPriceResolutionService::CONTEXT_SERVICE,
                    organizationUnitId: $organizationUnitId,
                    variantId: $batch->item_variant_id === null ? null : (int) $batch->item_variant_id,
                )
                : null;

            $batch->setAttribute('batch_price_revision_id', $batchPrice?->getKey());
            $batch->setAttribute('resolved_service_unit_price', $batchPrice?->amount ?? $fallback?->amount ?? '0.000000');
            $batch->setAttribute('price_source', $batchPrice === null ? ($fallback?->source ?? 'manual') : 'batch_price_revision');
        }

        return $paginator;
    }
}
