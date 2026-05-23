<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\Exceptions\WarehouseRecordNotFoundException;
use Modules\Warehouse\Presentation\Http\Controllers\Concerns\HandlesWarehouseHttp;
use Modules\Warehouse\Presentation\Http\Requests\StoreWarehouseLocationRequest;
use Modules\Warehouse\Presentation\Http\Requests\UpdateWarehouseLocationRequest;
use Modules\Warehouse\Presentation\Http\Resources\WarehouseLocationResource;

class WarehouseLocationController extends Controller
{
    use HandlesWarehouseHttp;

    public function __construct(private readonly WarehouseService $warehouses)
    {
    }

    public function index(Request $request, int|string $tenant, int|string $warehouse): mixed
    {
        try {
            return WarehouseLocationResource::collection($this->warehouses->listLocations(
                $tenant,
                $warehouse,
                $this->filters($request, ['organization_unit_id', 'parent_id', 'type', 'is_active']),
                $this->perPage($request),
            ));
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(
        StoreWarehouseLocationRequest $request,
        int|string $tenant,
        int|string $warehouse,
    ): JsonResponse {
        try {
            $location = $this->warehouses->createLocation(
                WarehouseLocationData::fromArray($tenant, $warehouse, $request->validated()),
            );

            return (new WarehouseLocationResource($location))->response()->setStatusCode(201);
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(
        int|string $tenant,
        int|string $warehouse,
        int|string $location,
    ): WarehouseLocationResource|JsonResponse {
        try {
            return new WarehouseLocationResource($this->warehouses->findLocation($tenant, $warehouse, $location));
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(
        UpdateWarehouseLocationRequest $request,
        int|string $tenant,
        int|string $warehouse,
        int|string $location,
    ): WarehouseLocationResource|JsonResponse {
        try {
            return new WarehouseLocationResource(
                $this->warehouses->updateLocation(
                    $tenant,
                    $warehouse,
                    $location,
                    WarehouseLocationData::fromArray($tenant, $warehouse, $request->validated()),
                ),
            );
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $warehouse, int|string $location): JsonResponse
    {
        try {
            $this->warehouses->deleteLocation($tenant, $warehouse, $location);

            return response()->json(null, 204);
        } catch (WarehouseRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
