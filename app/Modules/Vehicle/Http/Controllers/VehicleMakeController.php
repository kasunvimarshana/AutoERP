<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Requests\ListVehicleMasterRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleMakeRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleMakeRequest;
use Modules\Vehicle\Http\Resources\VehicleMakeResource;
use Modules\Vehicle\Services\VehicleMakeService;

final class VehicleMakeController
{
    public function __construct(private readonly VehicleMakeService $makes) {}

    public function index(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleMakeResource::collection($this->makes->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function store(StoreVehicleMakeRequest $request): JsonResponse
    {
        return (new VehicleMakeResource($this->makes->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListVehicleMasterRequest $request, int $make): VehicleMakeResource
    {
        return new VehicleMakeResource($this->makes->find($make, $request->tenantId(), $request->organizationUnitId()));
    }

    public function update(UpdateVehicleMakeRequest $request, int $make): VehicleMakeResource
    {
        return new VehicleMakeResource($this->makes->update($this->makes->find($make, $request->tenantId(), $request->organizationUnitId()), $request->toData()));
    }

    public function destroy(ListVehicleMasterRequest $request, int $make): JsonResponse
    {
        $this->makes->delete($this->makes->find($make, $request->tenantId(), $request->organizationUnitId()));
        return response()->json(null, 204);
    }

    public function lookup(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleMakeResource::collection($this->makes->lookup($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }
}
