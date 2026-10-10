<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Services\WarehouseItemAvailabilityLookup;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Item\Services\ItemQueryService;
use Modules\Selling\Http\Requests\SaleItemLookupRequest;
use Modules\Selling\Http\Requests\SaleQueryRequest;
use Modules\Selling\Http\Requests\StoreSaleRequest;
use Modules\Selling\Http\Requests\StoreSaleReturnRequest;
use Modules\Selling\Http\Resources\SaleResource;
use Modules\Selling\Http\Resources\SaleReturnResource;
use Modules\Selling\Http\Resources\SellingItemLookupResource;
use Modules\Selling\Models\Sale;
use Modules\Selling\Models\SaleReturn;
use Modules\Selling\Services\SalePostingService;
use Modules\Selling\Services\SellingAuthorizationService;

final class SaleController
{
    public function itemLookup(
        SaleItemLookupRequest $request,
        ItemQueryService $items,
        WarehouseItemAvailabilityLookup $availability,
        ItemAuthorizationService $authorization,
        ItemPriceResolutionService $prices,
    ): AnonymousResourceCollection {
        $authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        $criteria = [
            'search' => $request->validated('search'),
            'page' => $request->validated('page', 1),
            'per_page' => $request->validated('per_page', 20),
        ];
        $results = $items->lookup(
            $criteria,
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $criteria['per_page'],
            'stockable',
        );
        $availableByItemId = $availability->availableByItemIds(
            $results->getCollection()->modelKeys(),
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $request->validated('warehouse_id'),
            $request->filled('warehouse_location_id') ? (int) $request->validated('warehouse_location_id') : null,
        );
        $availableBatchesByItemId = $availability->availableBatchesByItemIds(
            $results->getCollection()->modelKeys(),
            $request->tenantId(),
            $request->organizationUnitId(),
            (int) $request->validated('warehouse_id'),
            $request->filled('warehouse_location_id') ? (int) $request->validated('warehouse_location_id') : null,
        );

        $results->getCollection()->each(function ($item) use ($availableByItemId, $availableBatchesByItemId, $prices, $request): void {
            $item->setAttribute('available_stock_quantity', $availableByItemId[(int) $item->getKey()] ?? '0.000000');
            $item->setAttribute('available_batches', $availableBatchesByItemId[(int) $item->getKey()] ?? []);
            $item->setAttribute('resolved_sales_unit_price', $prices->resolvePrice(
                item: $item,
                context: ItemPriceResolutionService::CONTEXT_SALES,
                organizationUnitId: $request->organizationUnitId(),
                date: (string) $request->validated('sale_date'),
            )->amount);
        });

        return SellingItemLookupResource::collection($results);
    }

    public function index(SaleQueryRequest $request): AnonymousResourceCollection
    {
        $query = Sale::query()
            ->with(['customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.uom', 'lines.variant', 'lines.batch', 'lines.serialNumber', 'invoice.balance'])
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->validated('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('sale_number', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$search.'%')->orWhere('display_name', 'like', '%'.$search.'%'));
                });
            })
            ->latest('sale_date')
            ->latest('id');

        return SaleResource::collection($query->paginate((int) $request->validated('per_page', 25)));
    }

    public function returns(SaleQueryRequest $request): AnonymousResourceCollection
    {
        $query = SaleReturn::query()
            ->with(['sale.customer', 'sale.invoice.balance', 'lines.saleLine.item'])
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->validated('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('return_number', 'like', '%'.$search.'%')
                        ->orWhereHas('sale', fn ($saleQuery) => $saleQuery->where('sale_number', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$search.'%')->orWhere('display_name', 'like', '%'.$search.'%')));
                });
            })
            ->latest('return_date')
            ->latest('id');

        return SaleReturnResource::collection($query->paginate((int) $request->validated('per_page', 25)));
    }

    public function show(SaleQueryRequest $request, int $sale, SellingAuthorizationService $authorization): SaleResource
    {
        $model = Sale::query()
            ->with(['customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.batch', 'lines.serialNumber', 'invoice.balance'])
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->findOrFail($sale);

        if ($authorization->can($request->currentUserId(), $request->tenantId(), SellingAuthorizationService::RETURNS_VIEW)) {
            $model->load('returns.lines');
        }

        return new SaleResource($model);
    }

    public function store(StoreSaleRequest $request, SalePostingService $service): SaleResource
    {
        $sale = $service->create($request->payload(), (string) $request->header('Idempotency-Key'));

        return new SaleResource($sale);
    }

    public function storeReturn(StoreSaleReturnRequest $request, SaleReturnPostingService $service): SaleResource
    {
        $sale = $service->create($request->payload(), (string) $request->header('Idempotency-Key'));

        return new SaleResource($sale);
    }
}
