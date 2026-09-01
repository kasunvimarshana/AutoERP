<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Resources\InventoryBatchResource;
use Modules\Inventory\Http\Resources\InventorySerialNumberResource;
use Modules\Inventory\Http\Resources\InventoryStockStateChangeResource;
use Modules\Inventory\Http\Resources\StockBalanceResource;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryStockStateChange;
use Modules\Inventory\Services\InventoryAvailabilityService;

final class StockController extends InventoryQueryController
{
    public function balances(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockBalance::query(), $request)
            ->with(['item', 'baseUom', 'variant', 'warehouse', 'warehouseLocation', 'batch']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
        ]);

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

    public function stateChanges(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockStateChange::query(), $request)
            ->with(['item', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'serialNumber']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'serial_number_id',
        ]);

        return InventoryStockStateChangeResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function batches(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryBatch::query(), $request)->with(['item', 'variant']);
        $this->filters($query, $request, ['item_id', 'item_variant_id', 'status']);
        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->input('search')).'%';
            $query->where(static fn (Builder $searchQuery): Builder => $searchQuery
                ->where('batch_number', 'like', $search)
                ->orWhere('lot_number', 'like', $search));
        }

        return InventoryBatchResource::collection($query->orderBy('batch_number')->paginate($request->perPage()));
    }

    public function serials(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventorySerialNumber::query(), $request)
            ->with(['item', 'variant', 'batch', 'warehouse', 'warehouseLocation']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'status',
        ]);
        if ($request->filled('search')) {
            $query->where('serial_number', 'like', '%'.trim((string) $request->input('search')).'%');
        }

        return InventorySerialNumberResource::collection($query->orderBy('serial_number')->paginate($request->perPage()));
    }
}
