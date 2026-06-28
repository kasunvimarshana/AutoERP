<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Customer\Http\Requests\EndCustomerVehicleRequest;
use Modules\Customer\Http\Requests\ListCustomerVehicleRequest;
use Modules\Customer\Http\Requests\StoreCustomerVehicleRequest;
use Modules\Customer\Http\Requests\UpdateCustomerVehicleRequest;
use Modules\Customer\Http\Resources\CustomerVehicleResource;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerVehicleQueryService;
use Modules\Customer\Services\CustomerVehicleService;

final class CustomerVehicleController
{
    public function __construct(private readonly CustomerVehicleQueryService $queries, private readonly CustomerVehicleService $service, private readonly CustomerAuthorizationService $authorization) {}

    public function index(ListCustomerVehicleRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_VIEW);

        return CustomerVehicleResource::collection($this->queries->paginate($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage()));
    }

    public function store(StoreCustomerVehicleRequest $request): JsonResponse
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_CREATE);

        return (new CustomerVehicleResource($this->service->create($request->validated(), $request->tenantId(), $request->organizationUnitId())))->response()->setStatusCode(201);
    }

    public function show(ListCustomerVehicleRequest $request, int $relationship): CustomerVehicleResource
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_VIEW);

        return new CustomerVehicleResource($this->find($request, $relationship));
    }

    public function update(UpdateCustomerVehicleRequest $request, int $relationship): CustomerVehicleResource
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_UPDATE);

        return new CustomerVehicleResource($this->service->update($this->find($request, $relationship), $request->validated()));
    }

    public function setCurrent(ListCustomerVehicleRequest $request, int $relationship): CustomerVehicleResource
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_SET_CURRENT);

        return new CustomerVehicleResource($this->service->setCurrent($this->find($request, $relationship)));
    }

    public function clearCurrent(ListCustomerVehicleRequest $request, int $relationship): CustomerVehicleResource
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_CLEAR_CURRENT);

        return new CustomerVehicleResource($this->service->clearCurrent($this->find($request, $relationship)));
    }

    public function destroy(EndCustomerVehicleRequest $request, int $relationship): CustomerVehicleResource
    {
        $this->authorize($request, CustomerAuthorizationService::VEHICLES_DELETE);

        return new CustomerVehicleResource($this->service->end($this->queries->find($relationship, $request->tenantId(), $request->organizationUnitId()), $request->validated('ended_at')));
    }

    private function find(ListCustomerVehicleRequest|UpdateCustomerVehicleRequest $request, int $id)
    {
        return $this->queries->find($id, $request->tenantId(), $request->organizationUnitId());
    }

    private function authorize($request, string $permission): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
    }
}
