<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Vehicle\Http\Requests\EndVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\ListVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\StoreVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\SupersedeVehicleOwnershipRequest;
use Modules\Vehicle\Http\Requests\VehicleOwnershipVersionRequest;
use Modules\Vehicle\Http\Resources\VehicleOwnershipResource;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\Vehicle\Services\VehicleAuthorizationService;
use Modules\Vehicle\Services\VehicleOwnershipQueryService;
use Modules\Vehicle\Services\VehicleOwnershipService;
use Modules\Vehicle\Services\VehicleQueryService;

final class VehicleOwnershipController
{
    public function __construct(
        private readonly VehicleOwnershipQueryService $queries,
        private readonly VehicleOwnershipService $service,
        private readonly VehicleQueryService $vehicles,
        private readonly VehicleAuthorizationService $authorization,
    ) {}

    public function index(ListVehicleOwnershipRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW_OWNERSHIPS);

        return VehicleOwnershipResource::collection($this->queries->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(),
        ));
    }

    public function store(StoreVehicleOwnershipRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_OWNERSHIPS);
        $vehicle = $this->vehicles->vehicle(
            (int) $request->validated('vehicle_id'), $request->tenantId(), $request->organizationUnitId(),
        );

        return (new VehicleOwnershipResource($this->service->assign($vehicle, $request->toData(), $request->currentUserId())))
            ->response()->setStatusCode(201);
    }

    public function show(ListVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::VIEW_OWNERSHIPS);

        return new VehicleOwnershipResource($this->find($request, $ownership));
    }

    public function supersede(SupersedeVehicleOwnershipRequest $request, int $ownership): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_OWNERSHIPS);
        $replacement = $this->service->supersede(
            $this->find($request, $ownership),
            $request->toData(),
            (int) $request->validated('expected_version'),
            (string) $request->validated('correction_reason'),
            $request->currentUserId(),
        );

        return (new VehicleOwnershipResource($replacement))->response()->setStatusCode(201);
    }

    public function setCurrent(VehicleOwnershipVersionRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->service->setCurrent(
            $this->find($request, $ownership), (int) $request->validated('expected_version'), $request->currentUserId(),
        ));
    }

    public function clearCurrent(VehicleOwnershipVersionRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->service->clearCurrent(
            $this->find($request, $ownership), (int) $request->validated('expected_version'), $request->currentUserId(),
        ));
    }

    public function end(EndVehicleOwnershipRequest $request, int $ownership): VehicleOwnershipResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleAuthorizationService::MANAGE_OWNERSHIPS);

        return new VehicleOwnershipResource($this->service->end(
            $this->find($request, $ownership),
            (int) $request->validated('expected_version'),
            (string) $request->validated('ended_at'),
            $request->currentUserId(),
        ));
    }

    private function find(ListVehicleOwnershipRequest|SupersedeVehicleOwnershipRequest|VehicleOwnershipVersionRequest|EndVehicleOwnershipRequest $request, int $id): VehicleOwnership
    {
        return $this->queries->find($id, $request->tenantId(), $request->organizationUnitId());
    }
}
