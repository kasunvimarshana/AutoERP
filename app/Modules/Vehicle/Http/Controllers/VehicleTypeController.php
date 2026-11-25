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
use Modules\Vehicle\Http\Requests\StoreVehicleTypeRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleTypeRequest;
use Modules\Vehicle\Http\Resources\VehicleTypeResource;
use Modules\Vehicle\Services\VehicleTypeService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $updated = DB::transaction(function () use ($request, $type, $expectedVersion) {
            $model = $this->types->find($type, $request->tenantId(), $request->organizationUnitId());
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Vehicle type was changed by someone else. Reload before saving.');
            }

            $nextVersion = ((int) $model->row_version) + 1;
            $saved = $this->types->update($model, $request->toData());
            $saved->forceFill(['row_version' => $nextVersion])->save();

            return $saved;
        });

        return new VehicleTypeResource($updated);
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
