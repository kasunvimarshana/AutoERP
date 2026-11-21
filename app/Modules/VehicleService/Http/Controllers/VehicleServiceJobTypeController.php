<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobTypesRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceJobTypeRequest;
use Modules\VehicleService\Http\Requests\UpdateVehicleServiceJobTypeRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceJobTypeResource;
use Modules\VehicleService\Services\VehicleServiceJobTypeService;

final class VehicleServiceJobTypeController
{
    public function __construct(private readonly VehicleServiceJobTypeService $service) {}

    public function index(ListVehicleServiceJobTypesRequest $request): AnonymousResourceCollection
    {
        return VehicleServiceJobTypeResource::collection(
            $this->service->paginate(
                $request->validated(),
                $request->tenantId(),
                $request->organizationUnitId(),
                $request->perPage(),
            ),
        );
    }

    public function lookup(ListVehicleServiceJobTypesRequest $request): AnonymousResourceCollection
    {
        return VehicleServiceJobTypeResource::collection(
            $this->service->lookup(
                $request->validated(),
                $request->tenantId(),
                $request->organizationUnitId(),
                $request->perPage(),
            ),
        );
    }

    public function store(StoreVehicleServiceJobTypeRequest $request): JsonResponse
    {
        $created = $this->service->create($request->toData());

        return (new VehicleServiceJobTypeResource($created))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListVehicleServiceJobTypesRequest $request, int $jobType): VehicleServiceJobTypeResource
    {
        return new VehicleServiceJobTypeResource(
            $this->service->find($jobType, $request->tenantId(), $request->organizationUnitId()),
        );
    }

    public function update(UpdateVehicleServiceJobTypeRequest $request, int $jobType): VehicleServiceJobTypeResource
    {
        $existing = $this->service->find($jobType, $request->tenantId(), $request->organizationUnitId());
        $updated = $this->service->update($existing, $request->toData());

        return new VehicleServiceJobTypeResource($updated);
    }

    public function destroy(ListVehicleServiceJobTypesRequest $request, int $jobType): JsonResponse
    {
        $existing = $this->service->find($jobType, $request->tenantId(), $request->organizationUnitId());
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;
        $this->service->delete($existing, $expectedVersion);

        return response()->json(null, 204);
    }
}
