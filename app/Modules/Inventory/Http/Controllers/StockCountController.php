<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
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

    public function store(StoreStockCountRequest $request, InventoryStockCountService $service): InventoryStockCountResource
    {
        return new InventoryStockCountResource($service->create($request->toData()));
    }

    public function approve(
        ReleaseQuantityRequest $request,
        int $count,
        InventoryStockCountService $service,
    ): InventoryStockCountResource {
        $model = $this->scope(InventoryStockCount::query(), $request)->with('lines')->findOrFail($count);

        return new InventoryStockCountResource($service->approve($model, $request->currentUserId()));
    }

    public function post(
        ReleaseQuantityRequest $request,
        int $count,
        InventoryStockCountService $service,
    ): InventoryStockCountResource {
        $model = $this->scope(InventoryStockCount::query(), $request)->with('lines')->findOrFail($count);

        return new InventoryStockCountResource($service->post($model, $request->currentUserId()));
    }
}
