<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Http\Requests\EndSupplierVehicleRequest;
use Modules\Supplier\Http\Requests\ListSupplierVehicleRequest;
use Modules\Supplier\Http\Requests\StoreSupplierVehicleRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierVehicleRequest;
use Modules\Supplier\Http\Resources\SupplierVehicleResource;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Supplier\Services\SupplierVehicleQueryService;
use Modules\Supplier\Services\SupplierVehicleService;

final class SupplierVehicleController
{
    public function __construct(private readonly SupplierVehicleQueryService $queries, private readonly SupplierVehicleService $service, private readonly SupplierAuthorizationService $authorization) {}

    public function index(ListSupplierVehicleRequest $r): AnonymousResourceCollection
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_VIEW);

        return SupplierVehicleResource::collection($this->queries->paginate($r->validated(), $r->tenantId(), $r->organizationUnitId(), $r->perPage()));
    }

    public function store(StoreSupplierVehicleRequest $r): JsonResponse
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_CREATE);

        return (new SupplierVehicleResource($this->service->create($r->validated(), $r->tenantId(), $r->organizationUnitId())))->response()->setStatusCode(201);
    }

    public function show(ListSupplierVehicleRequest $r, int $relationship): SupplierVehicleResource
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_VIEW);

        return new SupplierVehicleResource($this->find($r, $relationship));
    }

    public function update(UpdateSupplierVehicleRequest $r, int $relationship): SupplierVehicleResource
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_UPDATE);

        return new SupplierVehicleResource($this->service->update($this->find($r, $relationship), $r->validated()));
    }

    public function setCurrent(ListSupplierVehicleRequest $r, int $relationship): SupplierVehicleResource
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_SET_CURRENT);

        return new SupplierVehicleResource($this->service->setCurrent($this->find($r, $relationship)));
    }

    public function clearCurrent(ListSupplierVehicleRequest $r, int $relationship): SupplierVehicleResource
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_CLEAR_CURRENT);

        return new SupplierVehicleResource($this->service->clearCurrent($this->find($r, $relationship)));
    }

    public function destroy(EndSupplierVehicleRequest $r, int $relationship): SupplierVehicleResource
    {
        $this->auth($r, SupplierAuthorizationService::VEHICLES_DELETE);

        return new SupplierVehicleResource($this->service->end($this->queries->find($relationship, $r->tenantId(), $r->organizationUnitId()), $r->validated('ended_at')));
    }

    private function find($r, int $id)
    {
        return $this->queries->find($id, $r->tenantId(), $r->organizationUnitId());
    }

    private function auth($r, string $p): void
    {
        $this->authorization->assert($r->currentUserId(), $r->tenantId(), $p);
    }
}
