<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\StoreBatchPriceRequest;
use Modules\Inventory\Http\Requests\StoreInventoryBatchRequest;
use Modules\Inventory\Http\Requests\SupersedeBatchPriceRequest;
use Modules\Inventory\Http\Resources\InventoryBatchPriceResource;
use Modules\Inventory\Http\Resources\InventoryBatchResource;
use Modules\Inventory\Http\Resources\SellableBatchOptionResource;
use Modules\Inventory\Models\InventoryBatchPriceRevision;
use Modules\Inventory\Services\BatchPriceService;
use Modules\Inventory\Services\BatchTrackingService;
use Modules\Inventory\Services\SellableBatchLookupService;

final class BatchController extends InventoryQueryController
{
    public function serviceOptions(
        InventoryLookupRequest $request,
        SellableBatchLookupService $service,
    ): AnonymousResourceCollection {
        return SellableBatchOptionResource::collection($service->paginate(
            tenantId: $request->tenantId(),
            organizationUnitId: $request->organizationUnitId(),
            search: trim((string) $request->input('search', '')),
            perPage: $request->perPage(),
            warehouseId: $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            warehouseLocationId: $request->filled('warehouse_location_id') ? (int) $request->input('warehouse_location_id') : null,
        ));
    }

    public function store(StoreInventoryBatchRequest $request, BatchTrackingService $service): JsonResponse
    {
        $batch = $service->create(
            tenantId: $request->tenantId(),
            itemId: (int) $request->input('item_id'),
            batchNumber: (string) $request->input('batch_number'),
            organizationUnitId: $request->organizationUnitId(),
            itemVariantId: $request->filled('item_variant_id') ? (int) $request->input('item_variant_id') : null,
            lotNumber: $request->filled('lot_number') ? (string) $request->input('lot_number') : null,
            manufactureDate: $request->filled('manufacture_date') ? (string) $request->input('manufacture_date') : null,
            expiryDate: $request->filled('expiry_date') ? (string) $request->input('expiry_date') : null,
        );

        return (new InventoryBatchResource($batch))->response()->setStatusCode(201);
    }

    public function prices(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryBatchPriceRevision::query(), $request)
            ->with(['batch.item', 'organizationUnit', 'currency', 'uom']);
        if ($request->filled('batch_id')) {
            $query->where('batch_id', (int) $request->input('batch_id'));
        }
        if ($request->filled('item_id')) {
            $query->whereHas('batch', fn ($batch) => $batch->where('item_id', (int) $request->input('item_id')));
        }

        return InventoryBatchPriceResource::collection($query->latest('recorded_from')->paginate($request->perPage()));
    }

    public function storePrice(StoreBatchPriceRequest $request, BatchPriceService $service): JsonResponse
    {
        $price = $service->create($request->tenantId(), $request->toPriceData());

        return (new InventoryBatchPriceResource($price))->response()->setStatusCode(201);
    }

    public function supersedePrice(
        SupersedeBatchPriceRequest $request,
        int $price,
        BatchPriceService $service,
    ): InventoryBatchPriceResource {
        $current = InventoryBatchPriceRevision::query()
            ->where('tenant_id', $request->tenantId())
            ->findOrFail($price);

        return new InventoryBatchPriceResource($service->supersede(
            $request->tenantId(),
            $current,
            $request->toData(),
        ));
    }
}
