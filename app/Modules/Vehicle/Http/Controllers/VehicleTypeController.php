<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Requests\ListVehicleMasterRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleTypeRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleTypeRequest;
use Modules\Vehicle\Http\Resources\VehicleTypeResource;
use Modules\Vehicle\Services\VehicleTypeService;

final class VehicleTypeController
{
    public function __construct(private readonly VehicleTypeService $types) {}

    public function index(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleTypeResource::collection($this->types->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function store(StoreVehicleTypeRequest $request): JsonResponse
    {
        return (new VehicleTypeResource($this->types->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListVehicleMasterRequest $request, int $type): VehicleTypeResource
    {
        return new VehicleTypeResource($this->types->find($type, $request->tenantId(), $request->organizationUnitId()));
    }

    public function update(UpdateVehicleTypeRequest $request, int $type): VehicleTypeResource
    {
        return new VehicleTypeResource($this->types->update($this->types->find($type, $request->tenantId(), $request->organizationUnitId()), $request->toData()));
    }

    public function destroy(ListVehicleMasterRequest $request, int $type): JsonResponse
    {
        $this->types->delete($this->types->find($type, $request->tenantId(), $request->organizationUnitId()));
        return response()->json(null, 204);
    }

    public function lookup(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleTypeResource::collection($this->types->lookup($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }
}
