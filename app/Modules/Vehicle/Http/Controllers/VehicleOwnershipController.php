<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Requests\ListVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\MutateVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleOwnershipRequest;
use Modules\Vehicle\Http\Resources\VehicleOwnershipResource;
use Modules\Vehicle\Services\Ownership\VehicleOwnershipCommandService;
use Modules\Vehicle\Services\Ownership\VehicleOwnershipQueryService;
use Modules\Vehicle\Services\VehicleAuthorizationService;

final class VehicleOwnershipController
{
    public function __construct(
        private readonly VehicleOwnershipQueryService $queries,
        private readonly VehicleOwnershipCommandService $commands,
        private readonly VehicleAuthorizationService $authorization,
    ) {}

    public function index(ListVehicleOwnershipRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request, VehicleAuthorizationService::VIEW_OWNERSHIPS);

        return VehicleOwnershipResource::collection($this->queries->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreVehicleOwnershipRequest $request): JsonResponse
    {
        $this->authorize($request, VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return (new VehicleOwnershipResource($this->commands->create(
            $request->toData(),
            $request->tenantId(),
            $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorize($request, VehicleAuthorizationService::VIEW_OWNERSHIPS);

        return new VehicleOwnershipResource($this->find($request, $ownership));
    }

    public function update(UpdateVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorize($request, VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->commands->updateNotes($this->find($request, $ownership), $request->toCommand()));
    }

    public function setCurrent(MutateVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorize($request, VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->commands->setCurrent($this->find($request, $ownership), $request->toCommand()));
    }

    public function clearCurrent(MutateVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorize($request, VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->commands->clearCurrent($this->find($request, $ownership), $request->toCommand()));
    }

    public function destroy(MutateVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorize($request, VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->commands->end($this->find($request, $ownership), $request->toCommand()));
    }

    private function find(ListVehicleOwnershipRequest|UpdateVehicleOwnershipRequest|MutateVehicleOwnershipRequest $request, int $id)
    {
        return $this->queries->find($id, $request->tenantId(), $request->organizationUnitId());
    }

    private function authorize(object $request, string $permission): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
    }
}
