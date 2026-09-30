<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Http\Requests\ChangeVehicleStatusRequest;
use Modules\Vehicle\Http\Requests\GenerateVehicleCodeRequest;
use Modules\Vehicle\Http\Requests\ListVehicleRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleWithRelationsRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleRequest;
use Modules\Vehicle\Http\Resources\VehicleResource;
use Modules\Vehicle\Http\Resources\VehicleSummaryResource;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleCreationService;
use Modules\Vehicle\Services\VehicleNumberService;
use Modules\Vehicle\Services\VehicleQueryService;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\Vehicle\Services\VehicleUpdateService;

final class VehicleController
{
    public function __construct(
        private readonly VehicleQueryService $queries,
        private readonly VehicleCreationService $creation,
        private readonly VehicleNumberService $numbers,
        private readonly VehicleUpdateService $updates,
        private readonly VehicleStatusService $statuses,
        private readonly VehicleAuthorizationService $authorization,
    ) {}

    public function index(ListVehicleRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return VehicleSummaryResource::collection($this->queries->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::CREATE);

        return $this->created($this->creation->create($request->toData()));
    }

    public function generateCode(GenerateVehicleCodeRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::CREATE);

        return response()->json([
            'data' => ['code' => $this->numbers->nextCode($request->tenantId())],
        ]);
    }

    public function storeWithRelations(StoreVehicleWithRelationsRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::CREATE);

        return $this->created($this->creation->create($request->toData()));
    }

    public function show(ListVehicleRequest $request, int $vehicle): VehicleResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return new VehicleResource($this->queries->find(
            $vehicle,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateVehicleRequest $request, int $vehicle): VehicleResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::UPDATE);

        return new VehicleResource($this->updates->update(
            $this->queries->vehicle($vehicle, $request->tenantId(), $request->organizationUnitId()),
            $request->toData(),
        ));
    }

    public function destroy(ListVehicleRequest $request, int $vehicle): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::DELETE);

        $this->queries->delete($this->queries->vehicle(
            $vehicle,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function activate(ListVehicleRequest $request, int $vehicle): VehicleResource
    {
        return $this->changeTo($request, $vehicle, VehicleStatus::Active);
    }

    public function deactivate(ListVehicleRequest $request, int $vehicle): VehicleResource
    {
        return $this->changeTo($request, $vehicle, VehicleStatus::Inactive);
    }

    public function changeStatus(ChangeVehicleStatusRequest $request, int $vehicle): VehicleResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::CHANGE_STATUS);

        $model = $this->queries->vehicle($vehicle, $request->tenantId(), $request->organizationUnitId());

        return new VehicleResource($this->statuses->change($model, $request->toData())->load(['make', 'model', 'type', 'category', 'currentOwnerships']));
    }

    public function lookup(ListVehicleRequest $request, ?string $kind = null): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW);

        return VehicleSummaryResource::collection($this->queries->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
            $kind ?? 'all',
        ));
    }

    private function changeTo(ListVehicleRequest $request, int $vehicle, VehicleStatus $status): VehicleResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::CHANGE_STATUS);

        $model = $this->queries->vehicle($vehicle, $request->tenantId(), $request->organizationUnitId());

        return new VehicleResource($this->statuses->changeTo(
            $model,
            $status,
            $request->currentUserId(),
        )->load(['make', 'model', 'type', 'category', 'currentOwnerships']));
    }

    private function created(Vehicle $vehicle): JsonResponse
    {
        return (new VehicleResource($vehicle))->response()->setStatusCode(201);
    }
}
