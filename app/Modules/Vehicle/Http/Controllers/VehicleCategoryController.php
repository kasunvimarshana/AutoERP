<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Requests\ListVehicleMasterRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleCategoryRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleCategoryRequest;
use Modules\Vehicle\Http\Resources\VehicleCategoryResource;
use Modules\Vehicle\Services\VehicleCategoryService;

final class VehicleCategoryController
{
    public function __construct(private readonly VehicleCategoryService $categories) {}

    public function index(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleCategoryResource::collection($this->categories->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function store(StoreVehicleCategoryRequest $request): JsonResponse
    {
        return (new VehicleCategoryResource($this->categories->create($request->toData())->load('parent')))->response()->setStatusCode(201);
    }

    public function show(ListVehicleMasterRequest $request, int $category): VehicleCategoryResource
    {
        return new VehicleCategoryResource($this->categories->find($category, $request->tenantId(), $request->organizationUnitId()));
    }

    public function update(UpdateVehicleCategoryRequest $request, int $category): VehicleCategoryResource
    {
        return new VehicleCategoryResource($this->categories->update($this->categories->find($category, $request->tenantId(), $request->organizationUnitId()), $request->toData()));
    }

    public function destroy(ListVehicleMasterRequest $request, int $category): JsonResponse
    {
        $this->categories->delete($this->categories->find($category, $request->tenantId(), $request->organizationUnitId()));
        return response()->json(null, 204);
    }

    public function lookup(ListVehicleMasterRequest $request): AnonymousResourceCollection
    {
        return VehicleCategoryResource::collection($this->categories->lookup($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }
}
