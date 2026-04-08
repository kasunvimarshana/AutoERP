<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryCycleCountServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryCycleCountData;
use Modules\Inventory\Application\DTOs\UpdateInventoryCycleCountData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryCycleCountRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryCycleCountCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryCycleCountResource;

class InventoryCycleCountController extends AuthorizedController
{
    public function __construct(
        protected FindInventoryCycleCountServiceInterface $findService,
        protected CreateInventoryCycleCountServiceInterface $createService,
        protected UpdateInventoryCycleCountServiceInterface $updateService,
        protected DeleteInventoryCycleCountServiceInterface $deleteService,
        protected ReconcileInventoryServiceInterface $reconcileService,
    ) {}

    public function index(Request $request): InventoryCycleCountCollection
    {
        $filters = $request->only(['warehouse_id', 'status', 'tenant_id']);
        return new InventoryCycleCountCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreInventoryCycleCountRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = InventoryCycleCountData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'referenceNumber' => $v['reference_number'],
            'warehouseId'     => $v['warehouse_id'],
            'zoneId'          => $v['zone_id'] ?? null,
            'locationId'      => $v['location_id'] ?? null,
            'countMethod'     => $v['count_method'] ?? 'manual',
            'status'          => 'draft',
            'assignedTo'      => $v['assigned_to'] ?? null,
            'scheduledAt'     => $v['scheduled_at'] ?? null,
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
        ]);
        $count = $this->createService->execute($dto->toArray());
        return (new InventoryCycleCountResource($count))->response()->setStatusCode(201);
    }

    public function show(int $id): InventoryCycleCountResource|JsonResponse
    {
        $count = $this->findService->find($id);
        if (! $count) { return response()->json(['message' => 'Not found'], 404); }
        return new InventoryCycleCountResource($count);
    }

    public function update(UpdateInventoryCycleCountRequest $request, int $id): InventoryCycleCountResource
    {
        $dto = UpdateInventoryCycleCountData::fromArray(array_merge(['id' => $id], $request->validated()));
        return new InventoryCycleCountResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Cycle count deleted successfully']);
    }

    public function reconcile(int $id): InventoryCycleCountResource
    {
        $count = $this->reconcileService->execute(['id' => $id]);

        return new InventoryCycleCountResource($count);
    }
}
