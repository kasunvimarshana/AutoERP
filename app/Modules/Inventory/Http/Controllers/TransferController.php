<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
use Modules\Inventory\Http\Requests\StoreTransferRequest;
use Modules\Inventory\Http\Resources\InventoryTransferResource;
use Modules\Inventory\Models\InventoryTransfer;
use Modules\Inventory\Services\InventoryFacade;

final class TransferController extends InventoryQueryController
{
    public function index(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryTransfer::query(), $request)
            ->with(['fromWarehouse', 'fromWarehouseLocation', 'toWarehouse', 'toWarehouseLocation', 'lines.item', 'lines.baseUom', 'lines.enteredUom', 'lines.variant', 'lines.batch', 'lines.serialNumber']);
        $this->filters($query, $request, ['status']);

        return InventoryTransferResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function store(StoreTransferRequest $request, InventoryFacade $inventory): InventoryTransferResource
    {
        return new InventoryTransferResource($inventory->transfer($request->toData()));
    }

    public function post(ReleaseQuantityRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($inventory->postTransfer($model, $request->currentUserId()));
    }

    public function receive(ReleaseQuantityRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($inventory->receiveTransfer($model, $request->currentUserId()));
    }

    public function cancel(ReleaseQuantityRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->findOrFail($transfer);

        return new InventoryTransferResource($inventory->cancelTransfer($model, $request->currentUserId()));
    }
}
