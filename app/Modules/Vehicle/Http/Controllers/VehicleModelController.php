<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Requests\ListVehicleMasterRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleModelRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleModelRequest;
use Modules\Vehicle\Http\Resources\VehicleModelResource;
use Modules\Vehicle\Services\VehicleModelService;

final class VehicleModelController
{
    public function __construct(private readonly VehicleModelService $models) {}

    public function index(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleModelResource::collection($this->models->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function store(StoreVehicleModelRequest $request): JsonResponse
    {
        return (new VehicleModelResource($this->models->create($request->toData())->load('make')))->response()->setStatusCode(201);
    }

    public function show(ListVehicleMasterRequest $request, int $model): VehicleModelResource
    {
        return new VehicleModelResource($this->models->find($model, $request->tenantId(), $request->organizationUnitId()));
    }

    public function update(UpdateVehicleModelRequest $request, int $model): VehicleModelResource
    {
        return new VehicleModelResource($this->models->update($this->models->find($model, $request->tenantId(), $request->organizationUnitId()), $request->toData()));
    }

    public function destroy(ListVehicleMasterRequest $request, int $model): JsonResponse
    {
        $this->models->delete($this->models->find($model, $request->tenantId(), $request->organizationUnitId()));
        return response()->json(null, 204);
    }

    public function lookup(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleModelResource::collection($this->models->lookup($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }
}
