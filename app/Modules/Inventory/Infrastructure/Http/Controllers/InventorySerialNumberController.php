<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySerialNumberServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySerialNumberData;
use Modules\Inventory\Application\DTOs\UpdateInventorySerialNumberData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventorySerialNumberRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventorySerialNumberRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySerialNumberCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySerialNumberResource;

class InventorySerialNumberController extends AuthorizedController
{
    public function __construct(
        protected FindInventorySerialNumberServiceInterface $findService,
        protected CreateInventorySerialNumberServiceInterface $createService,
        protected UpdateInventorySerialNumberServiceInterface $updateService,
        protected DeleteInventorySerialNumberServiceInterface $deleteService,
    ) {}

    public function index(Request $request): InventorySerialNumberCollection
    {
        $filters = $request->only(['product_id', 'status', 'location_id', 'batch_id']);
        return new InventorySerialNumberCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreInventorySerialNumberRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = InventorySerialNumberData::fromArray([
            'tenantId'      => $v['tenant_id'],
            'productId'     => $v['product_id'],
            'variationId'   => $v['variation_id'] ?? null,
            'batchId'       => $v['batch_id'] ?? null,
            'serialNumber'  => $v['serial_number'],
            'locationId'    => $v['location_id'] ?? null,
            'status'        => $v['status'] ?? 'available',
            'purchasePrice' => $v['purchase_price'] ?? null,
            'currency'      => $v['currency'] ?? 'USD',
            'purchasedAt'   => $v['purchased_at'] ?? null,
            'notes'         => $v['notes'] ?? null,
            'metadata'      => $v['metadata'] ?? null,
        ]);
        $serial = $this->createService->execute($dto->toArray());
        return (new InventorySerialNumberResource($serial))->response()->setStatusCode(201);
    }

    public function show(int $id): InventorySerialNumberResource|JsonResponse
    {
        $serial = $this->findService->find($id);
        if (! $serial) { return response()->json(['message' => 'Not found'], 404); }
        return new InventorySerialNumberResource($serial);
    }

    public function update(UpdateInventorySerialNumberRequest $request, int $id): InventorySerialNumberResource
    {
        $dto = UpdateInventorySerialNumberData::fromArray(array_merge(['id' => $id], $request->validated()));
        return new InventorySerialNumberResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Serial number deleted successfully']);
    }
}
