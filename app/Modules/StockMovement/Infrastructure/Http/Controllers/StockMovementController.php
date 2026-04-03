<?php

declare(strict_types=1);

namespace Modules\StockMovement\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\StockMovement\Application\Contracts\ConfirmStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\CreateStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\DeleteStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\FindStockMovementServiceInterface;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\Contracts\UpdateStockMovementServiceInterface;
use Modules\StockMovement\Application\DTOs\StockMovementData;
use Modules\StockMovement\Application\DTOs\TransferStockData;
use Modules\StockMovement\Application\DTOs\UpdateStockMovementData;
use Modules\StockMovement\Infrastructure\Http\Requests\StoreStockMovementRequest;
use Modules\StockMovement\Infrastructure\Http\Requests\UpdateStockMovementRequest;
use Modules\StockMovement\Infrastructure\Http\Resources\StockMovementCollection;
use Modules\StockMovement\Infrastructure\Http\Resources\StockMovementResource;

class StockMovementController extends AuthorizedController
{
    public function __construct(
        protected FindStockMovementServiceInterface $findService,
        protected CreateStockMovementServiceInterface $createService,
        protected UpdateStockMovementServiceInterface $updateService,
        protected DeleteStockMovementServiceInterface $deleteService,
        protected ConfirmStockMovementServiceInterface $confirmService,
        protected TransferStockServiceInterface $transferService,
    ) {}

    public function index(Request $request): StockMovementCollection
    {
        $filters = $request->only(['product_id', 'movement_type', 'status', 'tenant_id']);
        return new StockMovementCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = StockMovementData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'referenceNumber' => $v['reference_number'],
            'movementType'    => $v['movement_type'],
            'productId'       => $v['product_id'],
            'quantity'        => $v['quantity'],
            'variationId'     => $v['variation_id'] ?? null,
            'fromLocationId'  => $v['from_location_id'] ?? null,
            'toLocationId'    => $v['to_location_id'] ?? null,
            'batchId'         => $v['batch_id'] ?? null,
            'serialNumberId'  => $v['serial_number_id'] ?? null,
            'uomId'           => $v['uom_id'] ?? null,
            'unitCost'        => $v['unit_cost'] ?? null,
            'currency'        => $v['currency'] ?? 'USD',
            'referenceType'   => $v['reference_type'] ?? null,
            'referenceId'     => $v['reference_id'] ?? null,
            'performedBy'     => $v['performed_by'] ?? null,
            'movementDate'    => $v['movement_date'] ?? null,
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
            'status'          => $v['status'] ?? 'draft',
        ]);
        $movement = $this->createService->execute($dto->toArray());
        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }

    public function show(int $id): StockMovementResource|JsonResponse
    {
        $movement = $this->findService->find($id);
        if (! $movement) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return new StockMovementResource($movement);
    }

    public function update(UpdateStockMovementRequest $request, int $id): StockMovementResource
    {
        $v   = $request->validated();
        $dto = UpdateStockMovementData::fromArray(array_merge(['id' => $id], $v));
        return new StockMovementResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Stock movement deleted successfully']);
    }

    public function confirm(int $id): StockMovementResource
    {
        return new StockMovementResource($this->confirmService->execute(['id' => $id]));
    }

    public function transfer(Request $request): JsonResponse
    {
        $v   = $request->validate([
            'tenant_id'        => 'required|integer',
            'reference_number' => 'required|string|max:100',
            'product_id'       => 'required|integer',
            'quantity'         => 'required|numeric|min:0.001',
            'from_location_id' => 'required|integer',
            'to_location_id'   => 'required|integer',
            'variation_id'     => 'nullable|integer',
            'batch_id'         => 'nullable|integer',
            'serial_number_id' => 'nullable|integer',
            'uom_id'           => 'nullable|integer',
            'unit_cost'        => 'nullable|numeric|min:0',
            'currency'         => 'nullable|string|size:3',
            'performed_by'     => 'nullable|integer',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
        ]);

        $dto      = TransferStockData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'referenceNumber' => $v['reference_number'],
            'productId'       => $v['product_id'],
            'quantity'        => $v['quantity'],
            'fromLocationId'  => $v['from_location_id'],
            'toLocationId'    => $v['to_location_id'],
            'variationId'     => $v['variation_id'] ?? null,
            'batchId'         => $v['batch_id'] ?? null,
            'serialNumberId'  => $v['serial_number_id'] ?? null,
            'uomId'           => $v['uom_id'] ?? null,
            'unitCost'        => $v['unit_cost'] ?? null,
            'currency'        => $v['currency'] ?? 'USD',
            'performedBy'     => $v['performed_by'] ?? null,
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
        ]);
        $movement = $this->transferService->execute($dto->toArray());

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }
}
