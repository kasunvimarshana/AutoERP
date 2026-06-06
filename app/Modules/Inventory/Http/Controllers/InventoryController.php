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
use Modules\Inventory\Http\Requests\StoreReservationRequest;
use Modules\Inventory\Http\Requests\StoreTransferRequest;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Http\Resources\InventoryAllocationResource;
use Modules\Inventory\Http\Resources\InventoryBatchResource;
use Modules\Inventory\Http\Resources\InventoryReservationResource;
use Modules\Inventory\Http\Resources\InventorySerialNumberResource;
use Modules\Inventory\Http\Resources\InventoryTransferResource;
use Modules\Inventory\Http\Resources\StockBalanceResource;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryTransfer;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAllocationService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockReservationService;
use Modules\Inventory\Services\StockTransferService;

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

    public function availability(InventoryLookupRequest $request, StockAvailabilityService $service): JsonResponse
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

    public function reserve(StoreReservationRequest $request, StockReservationService $service): InventoryReservationResource
    {
        return new InventoryReservationResource($service->reserve($request->toData()));
    }

    public function releaseReservation(ReleaseQuantityRequest $request, int $reservation, StockReservationService $service): InventoryReservationResource
    {
        $model = $this->scope(InventoryReservation::query(), $request)->findOrFail($reservation);

        return new InventoryReservationResource($service->release($model, $request->filled('quantity') ? (string) $request->input('quantity') : null));
    }

    public function allocate(StoreAllocationRequest $request, StockAllocationService $service): InventoryAllocationResource
    {
        return new InventoryAllocationResource($service->allocate($request->toData()));
    }

    public function releaseAllocation(ReleaseQuantityRequest $request, int $allocation, StockAllocationService $service): InventoryAllocationResource
    {
        $model = $this->scope(InventoryAllocation::query(), $request)->findOrFail($allocation);

        return new InventoryAllocationResource($service->release($model));
    }

    public function createAdjustment(StoreAdjustmentRequest $request, StockAdjustmentService $service): InventoryAdjustmentResource
    {
        return new InventoryAdjustmentResource($service->create($request->toData()));
    }

    public function postAdjustment(ReleaseQuantityRequest $request, int $adjustment, StockAdjustmentService $service): InventoryAdjustmentResource
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryAdjustmentResource($service->post($model, $request->currentUserId()));
    }

    public function createTransfer(StoreTransferRequest $request, StockTransferService $service): InventoryTransferResource
    {
        return new InventoryTransferResource($service->create($request->toData()));
    }

    public function postTransfer(ReleaseQuantityRequest $request, int $transfer, StockTransferService $service): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($service->post($model, $request->currentUserId()));
    }

    public function batches(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryBatch::query(), $request);

        return InventoryBatchResource::collection($this->filterTracking($query, $request, 'batch_number')->paginate($request->perPage()));
    }

    public function serials(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventorySerialNumber::query(), $request);

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
}
