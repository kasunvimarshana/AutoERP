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
use Modules\Inventory\Http\Requests\InventoryWorkflowActionRequest;
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

    public function showAdjustment(InventoryLookupRequest $request, int $adjustment): InventoryCostAdjustmentResource
    {
        $model = $this->scope(InventoryCostAdjustment::query(), $request)
            ->with('lines.valuationLayer.item')
            ->findOrFail($adjustment);

        return new InventoryCostAdjustmentResource($model);
    }

    public function storeAdjustment(
        StoreCostAdjustmentRequest $request,
        InventoryCostAdjustmentService $service,
    ): InventoryCostAdjustmentResource {
        return new InventoryCostAdjustmentResource($service->create($request->toData()));
    }

    public function postAdjustment(
        InventoryWorkflowActionRequest $request,
        int $adjustment,
        InventoryCostAdjustmentService $service,
    ): InventoryCostAdjustmentResource {
        $model = $this->scope(InventoryCostAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryCostAdjustmentResource($service->post($model, $request->currentUserId()));
    }

    public function destroyAdjustment(InventoryLookupRequest $request, int $adjustment): JsonResponse
    {
        $model = $this->scope(InventoryCostAdjustment::query(), $request)->findOrFail($adjustment);

        DB::transaction(function () use ($model): void {
            $model->lines()->delete();
            $model->delete();
        });

        return response()->json(['message' => 'Inventory cost adjustment deleted successfully.']);
    }
}
