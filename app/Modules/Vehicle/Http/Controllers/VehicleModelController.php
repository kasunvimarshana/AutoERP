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
use Modules\Vehicle\Http\Requests\StoreVehicleModelRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleModelRequest;
use Modules\Vehicle\Http\Resources\VehicleModelResource;
use Modules\Vehicle\Services\VehicleModelService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $updated = DB::transaction(function () use ($request, $model, $expectedVersion) {
            $record = $this->models->find($model, $request->tenantId(), $request->organizationUnitId());
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());

            if ($expectedVersion !== null && (int) $record->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Vehicle model was changed by someone else. Reload before saving.');
            }

            $nextVersion = ((int) $record->row_version) + 1;
            $saved = $this->models->update($record, $request->toData());
            $saved->forceFill(['row_version' => $nextVersion])->save();

            return $saved;
        });

        return new VehicleModelResource($updated);
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
