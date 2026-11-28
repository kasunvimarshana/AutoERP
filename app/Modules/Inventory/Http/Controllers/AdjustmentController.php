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

    public function show(InventoryLookupRequest $request, int $adjustment): InventoryAdjustmentResource
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)
            ->with(['warehouse', 'warehouseLocation', 'lines.item', 'lines.baseUom', 'lines.enteredUom', 'lines.variant', 'lines.batch', 'lines.serialNumber'])
            ->findOrFail($adjustment);

        return new InventoryAdjustmentResource($model);
    }

    public function store(StoreAdjustmentRequest $request, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        return new InventoryAdjustmentResource($inventory->adjust($request->toData()));
    }

    public function post(InventoryWorkflowActionRequest $request, int $adjustment, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        return new InventoryAdjustmentResource($inventory->postAdjustment($model, $request->currentUserId()));
    }

    public function reverse(InventoryWorkflowActionRequest $request, int $adjustment, InventoryFacade $inventory): InventoryAdjustmentResource
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)->with('lines')->findOrFail($adjustment);

        /** @var InventoryAdjustment $reversed */
        $reversed = $inventory->reverse($model, $request->currentUserId(), $request->input('reason'), $request->input('action_date'));

        return new InventoryAdjustmentResource($reversed->loadMissing([
            'warehouse',
            'warehouseLocation',
            'lines.item',
            'lines.baseUom',
            'lines.enteredUom',
            'lines.variant',
            'lines.batch',
            'lines.serialNumber',
        ]));
    }

    public function destroy(InventoryLookupRequest $request, int $adjustment): JsonResponse
    {
        $model = $this->scope(InventoryAdjustment::query(), $request)->findOrFail($adjustment);

        DB::transaction(function () use ($model): void {
            $model->lines()->delete();
            $model->delete();
        });

        return response()->json(['message' => 'Inventory adjustment deleted successfully.']);
    }
}
