<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Http\Requests\ListVehicleMasterRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleCategoryRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleCategoryRequest;
use Modules\Vehicle\Http\Resources\VehicleCategoryResource;
use Modules\Vehicle\Services\VehicleCategoryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $updated = DB::transaction(function () use ($request, $category, $expectedVersion) {
            $model = $this->categories->find($category, $request->tenantId(), $request->organizationUnitId());
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Vehicle category was changed by someone else. Reload before saving.');
            }

            $nextVersion = ((int) $model->row_version) + 1;
            $saved = $this->categories->update($model, $request->toData());
            $saved->forceFill(['row_version' => $nextVersion])->save();

            return $saved;
        });

        return new VehicleCategoryResource($updated);
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
