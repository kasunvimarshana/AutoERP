<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
use Modules\Inventory\Http\Requests\StoreCostAdjustmentRequest;
use Modules\Inventory\Http\Resources\InventoryCostAdjustmentResource;
use Modules\Inventory\Http\Resources\InventoryValuationLayerResource;
use Modules\Inventory\Models\InventoryCostAdjustment;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\InventoryCostAdjustmentService;

final class ValuationController extends InventoryQueryController
{
    public function layers(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryValuationLayer::query(), $request)
            ->with(['item.baseUom', 'baseUom', 'variant', 'warehouse', 'warehouseLocation', 'batch', 'movement']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'status',
        ]);

        return InventoryValuationLayerResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function adjustments(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryCostAdjustment::query(), $request)->with('lines.valuationLayer.item');
        $this->filters($query, $request, ['status']);

        return InventoryCostAdjustmentResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function storeAdjustment(
        StoreCostAdjustmentRequest $request,
        InventoryCostAdjustmentService $service,
    ): InventoryCostAdjustmentResource {
        return new InventoryCostAdjustmentResource($service->create($request->toData()));
    }

    public function postAdjustment(
        ReleaseQuantityRequest $request,
        int $adjustment,
        InventoryCostAdjustmentService $service,
    ): InventoryCostAdjustmentResource {
        $model = $this->scope(InventoryCostAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryCostAdjustmentResource($service->post($model, $request->currentUserId()));
    }
}
