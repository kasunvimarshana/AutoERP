<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryLocationServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryLocationServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryLocationData;
use Modules\Inventory\Application\DTOs\UpdateInventoryLocationData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryLocationRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryLocationRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLocationCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLocationResource;

class InventoryLocationController extends AuthorizedController
{
    public function __construct(
        protected FindInventoryLocationServiceInterface $findService,
        protected CreateInventoryLocationServiceInterface $createService,
        protected UpdateInventoryLocationServiceInterface $updateService,
        protected DeleteInventoryLocationServiceInterface $deleteService,
    ) {}

    public function index(Request $request): InventoryLocationCollection
    {
        $filters = $request->only(['warehouse_id', 'zone_id', 'type', 'is_active']);
        $locations = $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1));
        return new InventoryLocationCollection($locations);
    }

    public function store(StoreInventoryLocationRequest $request): JsonResponse
    {
        $v = $request->validated();
        $dto = InventoryLocationData::fromArray([
            'tenantId'    => $v['tenant_id'],
            'warehouseId' => $v['warehouse_id'],
            'zoneId'      => $v['zone_id'] ?? null,
            'code'        => $v['code'] ?? null,
            'name'        => $v['name'],
            'type'        => $v['type'] ?? 'bin',
            'aisle'       => $v['aisle'] ?? null,
            'row'         => $v['row'] ?? null,
            'level'       => $v['level'] ?? null,
            'bin'         => $v['bin'] ?? null,
            'capacity'    => $v['capacity'] ?? null,
            'weightLimit' => $v['weight_limit'] ?? null,
            'barcode'     => $v['barcode'] ?? null,
            'qrCode'      => $v['qr_code'] ?? null,
            'isPickable'  => $v['is_pickable'] ?? true,
            'isStorable'  => $v['is_storable'] ?? true,
            'isPacking'   => $v['is_packing'] ?? false,
            'isActive'    => $v['is_active'] ?? true,
            'metadata'    => $v['metadata'] ?? null,
        ]);
        $location = $this->createService->execute($dto->toArray());
        return (new InventoryLocationResource($location))->response()->setStatusCode(201);
    }

    public function show(int $id): InventoryLocationResource|JsonResponse
    {
        $location = $this->findService->find($id);
        if (! $location) { return response()->json(['message' => 'Not found'], 404); }
        return new InventoryLocationResource($location);
    }

    public function update(UpdateInventoryLocationRequest $request, int $id): InventoryLocationResource|JsonResponse
    {
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto = UpdateInventoryLocationData::fromArray([
            'id'          => $id,
            'code'        => $validated['code'] ?? null,
            'name'        => $validated['name'] ?? null,
            'type'        => $validated['type'] ?? null,
            'aisle'       => $validated['aisle'] ?? null,
            'row'         => $validated['row'] ?? null,
            'level'       => $validated['level'] ?? null,
            'bin'         => $validated['bin'] ?? null,
            'capacity'    => $validated['capacity'] ?? null,
            'weightLimit' => $validated['weight_limit'] ?? null,
            'barcode'     => $validated['barcode'] ?? null,
            'qrCode'      => $validated['qr_code'] ?? null,
            'isPickable'  => $validated['is_pickable'] ?? null,
            'isStorable'  => $validated['is_storable'] ?? null,
            'isPacking'   => $validated['is_packing'] ?? null,
            'isActive'    => $validated['is_active'] ?? null,
            'metadata'    => $validated['metadata'] ?? null,
        ]);
        $updated = $this->updateService->execute($dto->toArray());
        return new InventoryLocationResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Inventory location deleted successfully']);
    }
}
