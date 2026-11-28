<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
use Modules\Inventory\Http\Requests\StoreAllocationRequest;
use Modules\Inventory\Http\Resources\InventoryAllocationResource;
use Modules\Inventory\Models\InventoryAllocation;
use Modules\Inventory\Services\InventoryFacade;

final class AllocationController extends InventoryQueryController
{
    public function index(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryAllocation::query(), $request)
            ->with(['reservation', 'item', 'baseUom', 'enteredUom', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'serialNumber', 'lines', 'issues']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'serial_number_id',
            'status',
        ]);

        return InventoryAllocationResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function store(StoreAllocationRequest $request, InventoryFacade $inventory): InventoryAllocationResource
    {
        return new InventoryAllocationResource($inventory->allocate($request->toData()));
    }

    public function show(InventoryLookupRequest $request, int $allocation): InventoryAllocationResource
    {
        $model = $this->scope(InventoryAllocation::query(), $request)
            ->with(['reservation', 'item', 'baseUom', 'enteredUom', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'serialNumber', 'lines', 'issues'])
            ->findOrFail($allocation);

        return new InventoryAllocationResource($model);
    }

    public function destroy(InventoryLookupRequest $request, int $allocation, InventoryFacade $inventory): JsonResponse
    {
        $model = $this->scope(InventoryAllocation::query(), $request)->findOrFail($allocation);

        DB::transaction(function () use ($model, $request, $inventory): void {
            if (bccomp((string) $model->quantity_remaining, '0', 4) > 0) {
                $inventory->release($model, (string) $model->quantity_remaining, $request->currentUserId());
            }
            $model->delete();
        });

        return response()->json(['message' => 'Allocation deleted successfully.']);
    }

    public function issue(ReleaseQuantityRequest $request, int $allocation, InventoryFacade $inventory): InventoryAllocationResource
    {
        $model = $this->scope(InventoryAllocation::query(), $request)->findOrFail($allocation);

        return new InventoryAllocationResource($inventory->issueAllocation(
            $model,
            $request->filled('quantity') ? (string) $request->input('quantity') : null,
            $request->currentUserId(),
        ));
    }

    public function release(ReleaseQuantityRequest $request, int $allocation, InventoryFacade $inventory): InventoryAllocationResource
    {
        $model = $this->scope(InventoryAllocation::query(), $request)->findOrFail($allocation);

        return new InventoryAllocationResource($inventory->release(
            $model,
            $request->filled('quantity') ? (string) $request->input('quantity') : null,
            $request->currentUserId(),
        ));
    }
}
