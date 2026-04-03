<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\CreateInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\DeleteInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryLevelServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventoryLevelServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryLevelData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryLevelRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventoryLevelRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLevelCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLevelResource;

class InventoryLevelController extends AuthorizedController
{
    public function __construct(
        protected FindInventoryLevelServiceInterface $findService,
        protected CreateInventoryLevelServiceInterface $createService,
        protected UpdateInventoryLevelServiceInterface $updateService,
        protected DeleteInventoryLevelServiceInterface $deleteService,
        protected ReserveStockServiceInterface $reserveService,
        protected ReleaseStockServiceInterface $releaseService,
        protected AdjustInventoryServiceInterface $adjustService,
    ) {}

    public function index(Request $request): InventoryLevelCollection
    {
        $filters = $request->only(['product_id', 'location_id', 'batch_id', 'tenant_id']);
        return new InventoryLevelCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreInventoryLevelRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = InventoryLevelData::fromArray([
            'tenantId'    => $v['tenant_id'],
            'productId'   => $v['product_id'],
            'variationId' => $v['variation_id'] ?? null,
            'locationId'  => $v['location_id'] ?? null,
            'batchId'     => $v['batch_id'] ?? null,
            'uomId'       => $v['uom_id'] ?? null,
            'qtyOnHand'   => $v['qty_on_hand'] ?? 0.0,
            'qtyReserved' => $v['qty_reserved'] ?? 0.0,
            'qtyOnOrder'  => $v['qty_on_order'] ?? 0.0,
            'reorderPoint'=> $v['reorder_point'] ?? null,
            'reorderQty'  => $v['reorder_qty'] ?? null,
            'maxQty'      => $v['max_qty'] ?? null,
            'minQty'      => $v['min_qty'] ?? null,
        ]);
        $level = $this->createService->execute($dto->toArray());
        return (new InventoryLevelResource($level))->response()->setStatusCode(201);
    }

    public function show(int $id): InventoryLevelResource|JsonResponse
    {
        $level = $this->findService->find($id);
        if (! $level) { return response()->json(['message' => 'Not found'], 404); }
        return new InventoryLevelResource($level);
    }

    public function update(UpdateInventoryLevelRequest $request, int $id): InventoryLevelResource
    {
        $data         = $request->validated();
        $data['id']   = $id;
        return new InventoryLevelResource($this->updateService->execute($data));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Inventory level deleted successfully']);
    }

    public function reserve(Request $request, int $id): InventoryLevelResource
    {
        $level = $this->reserveService->execute([
            'id'  => $id,
            'qty' => (float) $request->input('qty', 0),
        ]);

        return new InventoryLevelResource($level);
    }

    public function release(Request $request, int $id): InventoryLevelResource
    {
        $level = $this->releaseService->execute([
            'id'  => $id,
            'qty' => (float) $request->input('qty', 0),
        ]);

        return new InventoryLevelResource($level);
    }

    public function adjust(Request $request, int $id): InventoryLevelResource
    {
        $level = $this->adjustService->execute([
            'id'            => $id,
            'adjustmentQty' => (float) $request->input('adjustment_qty'),
            'reason'        => $request->input('reason', ''),
            'adjustedBy'    => $request->integer('adjusted_by') ?: null,
            'notes'         => $request->input('notes'),
        ]);

        return new InventoryLevelResource($level);
    }
}
