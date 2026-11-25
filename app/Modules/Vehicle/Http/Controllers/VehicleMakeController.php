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
use Modules\Vehicle\Http\Requests\StoreVehicleMakeRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleMakeRequest;
use Modules\Vehicle\Http\Resources\VehicleMakeResource;
use Modules\Vehicle\Services\VehicleMakeService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $updated = DB::transaction(function () use ($request, $make, $expectedVersion) {
            $model = $this->makes->find($make, $request->tenantId(), $request->organizationUnitId());
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());

            if ($expectedVersion !== null && (int) $model->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Vehicle make was changed by someone else. Reload before saving.');
            }

            $nextVersion = ((int) $model->row_version) + 1;
            $saved = $this->makes->update($model, $request->toData());
            $saved->forceFill(['row_version' => $nextVersion])->save();

            return $saved;
        });

        return new VehicleMakeResource($updated);
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
