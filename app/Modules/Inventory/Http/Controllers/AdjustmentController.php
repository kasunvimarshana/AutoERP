<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
use Modules\Inventory\Http\Requests\StoreAdjustmentRequest;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Services\InventoryFacade;

final class AdjustmentController extends InventoryQueryController
{
    public function index(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryAdjustment::query(), $request)
            ->with(['warehouse', 'warehouseLocation', 'lines.item', 'lines.baseUom', 'lines.enteredUom', 'lines.variant', 'lines.batch', 'lines.serialNumber']);
        $this->filters($query, $request, ['warehouse_id', 'warehouse_location_id', 'status']);

        return InventoryAdjustmentResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function store(StoreAdjustmentRequest $request, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        return new InventoryAdjustmentResource($inventory->adjust($request->toData()));
    }

    public function post(ReleaseQuantityRequest $request, int $adjustment, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryAdjustmentResource($inventory->postAdjustment($model, $request->currentUserId()));
    }
}
