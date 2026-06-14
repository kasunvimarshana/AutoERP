<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
