<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryBatchServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryBatchData;
use Modules\Inventory\Application\DTOs\UpdateInventoryBatchData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryBatchRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryBatchRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryBatchCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryBatchResource;

class InventoryBatchController extends AuthorizedController
{
    public function __construct(
        protected FindInventoryBatchServiceInterface $findService,
        protected CreateInventoryBatchServiceInterface $createService,
        protected UpdateInventoryBatchServiceInterface $updateService,
        protected DeleteInventoryBatchServiceInterface $deleteService,
    ) {}

    public function index(Request $request): InventoryBatchCollection
    {
        $filters = $request->only(['product_id', 'status', 'tenant_id']);
        return new InventoryBatchCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreInventoryBatchRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = InventoryBatchData::fromArray([
            'tenantId'         => $v['tenant_id'],
            'productId'        => $v['product_id'],
            'variationId'      => $v['variation_id'] ?? null,
            'batchNumber'      => $v['batch_number'],
            'lotNumber'        => $v['lot_number'] ?? null,
            'manufactureDate'  => $v['manufacture_date'] ?? null,
            'expiryDate'       => $v['expiry_date'] ?? null,
            'bestBeforeDate'   => $v['best_before_date'] ?? null,
            'supplierId'       => $v['supplier_id'] ?? null,
            'supplierBatchRef' => $v['supplier_batch_ref'] ?? null,
            'initialQty'       => $v['initial_qty'] ?? 0.0,
            'unitCost'         => $v['unit_cost'] ?? 0.0,
            'currency'         => $v['currency'] ?? 'USD',
            'status'           => $v['status'] ?? 'active',
            'notes'            => $v['notes'] ?? null,
            'metadata'         => $v['metadata'] ?? null,
        ]);
        $batch = $this->createService->execute($dto->toArray());
        return (new InventoryBatchResource($batch))->response()->setStatusCode(201);
    }

    public function show(int $id): InventoryBatchResource|JsonResponse
    {
        $batch = $this->findService->find($id);
        if (! $batch) { return response()->json(['message' => 'Not found'], 404); }
        return new InventoryBatchResource($batch);
    }

    public function update(UpdateInventoryBatchRequest $request, int $id): InventoryBatchResource
    {
        $v   = $request->validated();
        $dto = UpdateInventoryBatchData::fromArray(array_merge(['id' => $id], $v));
        return new InventoryBatchResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Inventory batch deleted successfully']);
    }
}
