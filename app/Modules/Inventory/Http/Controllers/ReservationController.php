<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Http\Requests\ReleaseQuantityRequest;
use Modules\Inventory\Http\Requests\StoreReservationRequest;
use Modules\Inventory\Http\Resources\InventoryReservationResource;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Services\InventoryFacade;

final class ReservationController extends InventoryQueryController
{
    public function index(InventoryLookupRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(InventoryReservation::query(), $request)
            ->with(['item', 'baseUom', 'enteredUom', 'variant', 'warehouse', 'warehouseLocation', 'batch']);
        $this->filters($query, $request, [
            'item_id',
            'item_variant_id',
            'warehouse_id',
            'warehouse_location_id',
            'batch_id',
            'status',
        ]);

        return InventoryReservationResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function store(StoreReservationRequest $request, InventoryFacade $inventory): InventoryReservationResource
    {
        return new InventoryReservationResource($inventory->reserve($request->toData()));
    }

    public function release(ReleaseQuantityRequest $request, int $reservation, InventoryFacade $inventory): InventoryReservationResource
    {
        $model = $this->scope(InventoryReservation::query(), $request)->findOrFail($reservation);

        return new InventoryReservationResource($inventory->unreserve(
            $model,
            $request->filled('quantity') ? (string) $request->input('quantity') : null,
            $request->currentUserId(),
        ));
    }
}
