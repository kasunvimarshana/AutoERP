<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Dispatch\Application\Contracts\CreateDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\DeleteDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\FindDispatchLineServiceInterface;
use Modules\Dispatch\Application\Contracts\UpdateDispatchLineServiceInterface;
use Modules\Dispatch\Application\DTOs\DispatchLineData;
use Modules\Dispatch\Application\DTOs\UpdateDispatchLineData;
use Modules\Dispatch\Infrastructure\Http\Requests\StoreDispatchLineRequest;
use Modules\Dispatch\Infrastructure\Http\Requests\UpdateDispatchLineRequest;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchLineCollection;
use Modules\Dispatch\Infrastructure\Http\Resources\DispatchLineResource;

class DispatchLineController extends AuthorizedController
{
    public function __construct(
        protected FindDispatchLineServiceInterface $findService,
        protected CreateDispatchLineServiceInterface $createService,
        protected UpdateDispatchLineServiceInterface $updateService,
        protected DeleteDispatchLineServiceInterface $deleteService,
    ) {}

    public function index(Request $request): DispatchLineCollection
    {
        $filters = $request->only(['tenant_id', 'dispatch_id']);

        return new DispatchLineCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreDispatchLineRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = DispatchLineData::fromArray([
            'tenantId'           => $v['tenant_id'],
            'dispatchId'         => $v['dispatch_id'],
            'productId'          => $v['product_id'],
            'quantity'           => $v['quantity'],
            'salesOrderLineId'   => $v['sales_order_line_id'] ?? null,
            'productVariantId'   => $v['product_variant_id'] ?? null,
            'description'        => $v['description'] ?? null,
            'unitOfMeasure'      => $v['unit_of_measure'] ?? null,
            'warehouseLocationId'=> $v['warehouse_location_id'] ?? null,
            'batchNumber'        => $v['batch_number'] ?? null,
            'serialNumber'       => $v['serial_number'] ?? null,
            'status'             => $v['status'] ?? 'pending',
            'weight'             => $v['weight'] ?? null,
            'notes'              => $v['notes'] ?? null,
            'metadata'           => $v['metadata'] ?? null,
        ]);

        $line = $this->createService->execute($dto->toArray());

        return (new DispatchLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): DispatchLineResource|JsonResponse
    {
        $line = $this->findService->find($id);
        if (! $line) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new DispatchLineResource($line);
    }

    public function update(UpdateDispatchLineRequest $request, int $id): DispatchLineResource
    {
        $v   = $request->validated();
        $dto = UpdateDispatchLineData::fromArray([
            'id'                 => $id,
            'description'        => $v['description'] ?? null,
            'quantity'           => $v['quantity'] ?? null,
            'warehouseLocationId'=> $v['warehouse_location_id'] ?? null,
            'batchNumber'        => $v['batch_number'] ?? null,
            'serialNumber'       => $v['serial_number'] ?? null,
            'weight'             => $v['weight'] ?? null,
            'notes'              => $v['notes'] ?? null,
            'metadata'           => $v['metadata'] ?? null,
        ]);

        return new DispatchLineResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Dispatch line deleted successfully']);
    }
}
