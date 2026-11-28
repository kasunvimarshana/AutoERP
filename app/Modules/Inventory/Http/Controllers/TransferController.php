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

    public function show(InventoryLookupRequest $request, int $transfer): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)
            ->with(['fromWarehouse', 'fromWarehouseLocation', 'toWarehouse', 'toWarehouseLocation', 'lines.item', 'lines.baseUom', 'lines.enteredUom', 'lines.variant', 'lines.batch', 'lines.serialNumber'])
            ->findOrFail($transfer);

        return new InventoryTransferResource($model);
    }

    public function store(StoreTransferRequest $request, InventoryFacade $inventory): InventoryTransferResource
    {
        return new InventoryTransferResource($inventory->transfer($request->toData()));
    }

    public function post(InventoryWorkflowActionRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($inventory->postTransfer($model, $request->currentUserId()));
    }

    public function receive(InventoryWorkflowActionRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        return new InventoryTransferResource($inventory->receiveTransfer($model, $request->currentUserId()));
    }

    public function reverse(InventoryWorkflowActionRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->with('lines')->findOrFail($transfer);

        /** @var InventoryTransfer $reversed */
        $reversed = $inventory->reverse($model, $request->currentUserId(), $request->input('reason'), $request->input('action_date'));

        return new InventoryTransferResource($reversed->loadMissing([
            'fromWarehouse',
            'fromWarehouseLocation',
            'toWarehouse',
            'toWarehouseLocation',
            'lines.item',
            'lines.baseUom',
            'lines.enteredUom',
            'lines.variant',
            'lines.batch',
            'lines.serialNumber',
        ]));
    }

    public function cancel(InventoryWorkflowActionRequest $request, int $transfer, InventoryFacade $inventory): InventoryTransferResource
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->findOrFail($transfer);

        return new InventoryTransferResource($inventory->cancelTransfer($model, $request->currentUserId()));
    }

    public function destroy(InventoryLookupRequest $request, int $transfer): JsonResponse
    {
        $model = $this->scope(InventoryTransfer::query(), $request)->findOrFail($transfer);

        DB::transaction(function () use ($model): void {
            $model->lines()->delete();
            $model->delete();
        });

        return response()->json(['message' => 'Inventory transfer deleted successfully.']);
    }
}
