<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\Exceptions\WarehouseRecordNotFoundException;
use Modules\Warehouse\Presentation\Http\Controllers\Concerns\HandlesWarehouseHttp;
use Modules\Warehouse\Presentation\Http\Requests\StoreWarehouseRequest;
use Modules\Warehouse\Presentation\Http\Requests\UpdateWarehouseRequest;
use Modules\Warehouse\Presentation\Http\Resources\WarehouseResource;

class WarehouseController extends Controller
{
    use HandlesWarehouseHttp;

    public function __construct(private readonly WarehouseService $warehouses)
    {
    }

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return WarehouseResource::collection($this->warehouses->listWarehouses(
                $tenant,
                $this->filters($request, ['organization_unit_id', 'type', 'is_active', 'is_default']),
                $this->perPage($request),
            ));
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreWarehouseRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $warehouse = $this->warehouses->createWarehouse(WarehouseData::fromArray($tenant, $request->validated()));

            return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $warehouse): WarehouseResource|JsonResponse
    {
        try {
            return new WarehouseResource($this->warehouses->findWarehouse($tenant, $warehouse));
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(
        UpdateWarehouseRequest $request,
        int|string $tenant,
        int|string $warehouse,
    ): WarehouseResource|JsonResponse {
        try {
            return new WarehouseResource(
                $this->warehouses->updateWarehouse(
                    $tenant,
                    $warehouse,
                    WarehouseData::fromArray($tenant, $request->validated()),
                ),
            );
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $warehouse): JsonResponse
    {
        try {
            $this->warehouses->deleteWarehouse($tenant, $warehouse);

            return response()->json(null, 204);
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
