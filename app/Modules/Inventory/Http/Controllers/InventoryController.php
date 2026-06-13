<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
use Modules\Inventory\Http\Requests\StoreAdjustmentRequest;
use Modules\Inventory\Http\Requests\StoreAllocationRequest;
use Modules\Inventory\Http\Requests\StoreCostAdjustmentRequest;
use Modules\Inventory\Http\Requests\StoreReservationRequest;
use Modules\Inventory\Http\Requests\StoreStockCountRequest;
use Modules\Inventory\Http\Requests\StoreTransferRequest;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Http\Resources\InventoryAllocationResource;
use Modules\Inventory\Http\Resources\InventoryBatchResource;
use Modules\Inventory\Http\Resources\InventoryCostAdjustmentResource;
use Modules\Inventory\Http\Resources\InventoryReservationResource;
use Modules\Inventory\Http\Resources\InventorySerialNumberResource;
use Modules\Inventory\Http\Resources\InventoryStockCountResource;
use Modules\Inventory\Http\Resources\InventoryStockStateChangeResource;
use Modules\Inventory\Http\Resources\InventoryTransferResource;
use Modules\Inventory\Http\Resources\InventoryValuationLayerResource;
use Modules\Inventory\Http\Resources\StockBalanceResource;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryCostAdjustment;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryStockCount;
use Modules\Inventory\Models\InventoryStockStateChange;
use Modules\Inventory\Models\InventoryTransfer;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\InventoryAvailabilityService;
use Modules\Inventory\Services\InventoryCostAdjustmentService;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Inventory\Services\InventoryStockCountService;

final class InventoryController
{
    public function stockBalances(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockBalance::query(), $request)
            ->with(['item', 'variant', 'warehouse', 'warehouseLocation', 'batch']);
        foreach (['item_id', 'item_variant_id', 'warehouse_id', 'warehouse_location_id', 'batch_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, (int) $request->input($filter));
            }
        }

        return StockBalanceResource::collection($query->orderBy('item_id')->paginate($request->perPage()));
    }

    public function availability(InventoryLookupRequest $request, InventoryAvailabilityService $service): JsonResponse
    {
        $request->validate([
            'item_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
        ]);
        $result = $service->availability(new StockBalanceData(
            tenantId: $request->tenantId(),
            itemId: (int) $request->input('item_id'),
            warehouseId: (int) $request->input('warehouse_id'),
            organizationUnitId: $request->organizationUnitId(),
            itemVariantId: $request->filled('item_variant_id') ? (int) $request->input('item_variant_id') : null,
            warehouseLocationId: $request->filled('warehouse_location_id') ? (int) $request->input('warehouse_location_id') : null,
            batchId: $request->filled('batch_id') ? (int) $request->input('batch_id') : null,
        ));

        return response()->json(['data' => get_object_vars($result)]);
    }

    public function reservations(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryReservation::query(), $request)
            ->with(['item', 'baseUom', 'variant', 'warehouse', 'warehouseLocation', 'batch']);

        return InventoryReservationResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function reserve(StoreReservationRequest $request, InventoryFacade $inventory): InventoryReservationResource
    {
        return new InventoryReservationResource($inventory->reserve($request->toData()));
    }

    public function releaseReservation(ReleaseQuantityRequest $request, int $reservation, InventoryFacade $inventory): InventoryReservationResource
    {
        $model = $this->scope(InventoryReservation::query(), $request)->findOrFail($reservation);

        return new InventoryReservationResource($inventory->unreserve(
            $model,
            $request->filled('quantity') ? (string) $request->input('quantity') : null,
            $request->currentUserId(),
        ));
    }

    public function allocations(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryAllocation::query(), $request)
            ->with(['reservation', 'item', 'baseUom', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'serialNumber', 'lines', 'issues']);

        return InventoryAllocationResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function allocate(StoreAllocationRequest $request, InventoryFacade $inventory): InventoryAllocationResource
    {
        return new InventoryAllocationResource($inventory->allocate($request->toData()));
    }

    public function releaseAllocation(ReleaseQuantityRequest $request, int $allocation, InventoryFacade $inventory): InventoryAllocationResource
    {
        $model = $this->scope(InventoryAllocation::query(), $request)->findOrFail($allocation);

        return new InventoryAllocationResource($inventory->release(
            $model,
            $request->filled('quantity') ? (string) $request->input('quantity') : null,
            $request->currentUserId(),
        ));
    }

    public function issueAllocation(ReleaseQuantityRequest $request, int $allocation, InventoryFacade $inventory): InventoryAllocationResource
    {
        $model = $this->scope(InventoryAllocation::query(), $request)->findOrFail($allocation);

        return new InventoryAllocationResource($inventory->issueAllocation(
            $model,
            $request->filled('quantity') ? (string) $request->input('quantity') : null,
            $request->currentUserId(),
        ));
    }

    public function adjustments(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryAdjustment::query(), $request)
            ->with(['warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.batch', 'lines.serialNumber']);

        return InventoryAdjustmentResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function createAdjustment(StoreAdjustmentRequest $request, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        return new InventoryAdjustmentResource($inventory->adjust($request->toData()));
    }

    public function postAdjustment(ReleaseQuantityRequest $request, int $adjustment, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryAdjustmentResource($inventory->postAdjustment($model, $request->currentUserId()));
    }

    public function transfers(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryTransfer::query(), $request)
            ->with(['fromWarehouse', 'fromWarehouseLocation', 'toWarehouse', 'toWarehouseLocation', 'lines.item', 'lines.variant', 'lines.batch', 'lines.serialNumber']);

        return InventoryTransferResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function createTransfer(StoreTransferRequest $request, InventoryFacade $inventory): InventoryTransferResource
    {
        return new InventoryTransferResource($inventory->transfer($request->toData()));
    }

    public function postTransfer(ReleaseQuantityRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($inventory->postTransfer($model, $request->currentUserId()));
    }

    public function receiveTransfer(ReleaseQuantityRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($inventory->receiveTransfer($model, $request->currentUserId()));
    }

    public function cancelTransfer(ReleaseQuantityRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->findOrFail($transfer);

        return new InventoryTransferResource($inventory->cancelTransfer($model, $request->currentUserId()));
    }

    public function stateChanges(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockStateChange::query(), $request)
            ->with(['item', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'serialNumber']);

        return InventoryStockStateChangeResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function costAdjustments(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryCostAdjustment::query(), $request)->with('lines.valuationLayer.item');

        return InventoryCostAdjustmentResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function valuationLayers(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryValuationLayer::query(), $request)
            ->with(['item.baseUom', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'movement']);

        return InventoryValuationLayerResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function createCostAdjustment(StoreCostAdjustmentRequest $request, InventoryCostAdjustmentService $service): InventoryCostAdjustmentResource
    {
        return new InventoryCostAdjustmentResource($service->create($request->toData()));
    }

    public function postCostAdjustment(ReleaseQuantityRequest $request, int $adjustment, InventoryCostAdjustmentService $service): InventoryCostAdjustmentResource
    {
        $model = $this->scope(InventoryCostAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryCostAdjustmentResource($service->post($model, $request->currentUserId()));
    }

    public function stockCounts(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockCount::query(), $request)->with(['warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.batch', 'lines.serialNumber', 'adjustment']);

        return InventoryStockCountResource::collection($this->filterInventory($query, $request)->latest('id')->paginate($request->perPage()));
    }

    public function createStockCount(StoreStockCountRequest $request, InventoryStockCountService $service): InventoryStockCountResource
    {
        return new InventoryStockCountResource($service->create($request->toData()));
    }

    public function approveStockCount(ReleaseQuantityRequest $request, int $count, InventoryStockCountService $service): InventoryStockCountResource
    {
        $model = $this->scope(InventoryStockCount::query(), $request)->with('lines')->findOrFail($count);

        return new InventoryStockCountResource($service->approve($model, $request->currentUserId()));
    }

    public function postStockCount(ReleaseQuantityRequest $request, int $count, InventoryStockCountService $service): InventoryStockCountResource
    {
        $model = $this->scope(InventoryStockCount::query(), $request)->with('lines')->findOrFail($count);

        return new InventoryStockCountResource($service->post($model, $request->currentUserId()));
    }

    public function batches(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryBatch::query(), $request)
            ->with(['item', 'variant']);

        return InventoryBatchResource::collection($this->filterTracking($query, $request, 'batch_number')->paginate($request->perPage()));
    }

    public function serials(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventorySerialNumber::query(), $request)
            ->with(['item', 'variant', 'batch', 'warehouse', 'warehouseLocation']);

        return InventorySerialNumberResource::collection($this->filterTracking($query, $request, 'serial_number')->paginate($request->perPage()));
    }

    private function scope(Builder $query, InventoryLookupRequest|ReleaseQuantityRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }

    private function filterTracking(Builder $query, InventoryLookupRequest $request, string $numberColumn): Builder
    {
        foreach (['item_id', 'item_variant_id', 'warehouse_id', 'warehouse_location_id', 'batch_id', 'status'] as $filter) {
            if ($request->filled($filter) && Schema::hasColumn($query->getModel()->getTable(), $filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('search')) {
            $query->where($numberColumn, 'like', '%'.trim((string) $request->input('search')).'%');
        }

        return $query->orderBy($numberColumn);
    }

    private function filterInventory(Builder $query, InventoryLookupRequest $request): Builder
    {
        foreach (['item_id', 'item_variant_id', 'warehouse_id', 'warehouse_location_id', 'batch_id', 'serial_number_id', 'status'] as $filter) {
            if ($request->filled($filter) && Schema::hasColumn($query->getModel()->getTable(), $filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        return $query;
    }
}
