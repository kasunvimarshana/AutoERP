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
use Modules\Inventory\Http\Requests\StoreStockCountRequest;
use Modules\Inventory\Http\Resources\InventoryStockCountResource;
use Modules\Inventory\Models\InventoryStockCount;
use Modules\Inventory\Services\InventoryStockCountService;

final class StockCountController extends InventoryQueryController
{
    public function index(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryStockCount::query(), $request)
            ->with(['warehouse', 'warehouseLocation', 'lines.item', 'lines.baseUom', 'lines.enteredUom', 'lines.variant', 'lines.batch', 'lines.serialNumber', 'adjustment']);
        $this->filters($query, $request, ['warehouse_id', 'warehouse_location_id', 'status']);

        return InventoryStockCountResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function show(InventoryLookupRequest $request, int $count): InventoryStockCountResource
    {
        $model = $this->scope(InventoryStockCount::query(), $request)
            ->with(['warehouse', 'warehouseLocation', 'lines.item', 'lines.baseUom', 'lines.enteredUom', 'lines.variant', 'lines.batch', 'lines.serialNumber', 'adjustment'])
            ->findOrFail($count);

        return new InventoryStockCountResource($model);
    }

    public function store(StoreStockCountRequest $request, InventoryStockCountService $service): InventoryStockCountResource
    {
        return new InventoryStockCountResource($service->create($request->toData()));
    }

    public function approve(
        InventoryWorkflowActionRequest $request,
        int $count,
        InventoryStockCountService $service,
    ): InventoryStockCountResource {
        $model = $this->scope(InventoryStockCount::query(), $request)->with('lines')->findOrFail($count);

        return new InventoryStockCountResource($service->approve($model, $request->currentUserId()));
    }

    public function post(
        InventoryWorkflowActionRequest $request,
        int $count,
        InventoryStockCountService $service,
    ): InventoryStockCountResource {
        $model = $this->scope(InventoryStockCount::query(), $request)->with('lines')->findOrFail($count);

        return new InventoryStockCountResource($service->post($model, $request->currentUserId()));
    }

    public function destroy(InventoryLookupRequest $request, int $count): JsonResponse
    {
        $model = $this->scope(InventoryStockCount::query(), $request)->findOrFail($count);

        DB::transaction(function () use ($model): void {
            $model->lines()->delete();
            $model->delete();
        });

        return response()->json(['message' => 'Inventory stock count deleted successfully.']);
    }
}
