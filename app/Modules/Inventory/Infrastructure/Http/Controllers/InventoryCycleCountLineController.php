<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryCycleCountLineData;
use Modules\Inventory\Application\DTOs\UpdateInventoryCycleCountLineData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryCycleCountLineRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryCycleCountLineRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryCycleCountLineCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryCycleCountLineResource;

class InventoryCycleCountLineController extends AuthorizedController
{
    public function __construct(
        protected FindInventoryCycleCountLineServiceInterface $findService,
        protected CreateInventoryCycleCountLineServiceInterface $createService,
        protected UpdateInventoryCycleCountLineServiceInterface $updateService,
        protected DeleteInventoryCycleCountLineServiceInterface $deleteService,
    ) {}

    public function index(Request $request): InventoryCycleCountLineCollection
    {
        $filters = $request->only(['cycle_count_id', 'product_id', 'status']);
        return new InventoryCycleCountLineCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreInventoryCycleCountLineRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = InventoryCycleCountLineData::fromArray([
            'tenantId'       => $v['tenant_id'],
            'cycleCountId'   => $v['cycle_count_id'],
            'productId'      => $v['product_id'],
            'variationId'    => $v['variation_id'] ?? null,
            'batchId'        => $v['batch_id'] ?? null,
            'serialNumberId' => $v['serial_number_id'] ?? null,
            'locationId'     => $v['location_id'] ?? null,
            'expectedQty'    => $v['expected_qty'] ?? 0.0,
            'notes'          => $v['notes'] ?? null,
        ]);
        $line = $this->createService->execute($dto->toArray());
        return (new InventoryCycleCountLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): InventoryCycleCountLineResource|JsonResponse
    {
        $line = $this->findService->find($id);
        if (! $line) { return response()->json(['message' => 'Not found'], 404); }
        return new InventoryCycleCountLineResource($line);
    }

    public function update(UpdateInventoryCycleCountLineRequest $request, int $id): InventoryCycleCountLineResource
    {
        $dto = UpdateInventoryCycleCountLineData::fromArray(array_merge(['id' => $id], $request->validated()));
        return new InventoryCycleCountLineResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Cycle count line deleted successfully']);
    }
}
