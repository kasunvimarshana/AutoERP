<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Http\Requests\ChangeCustomerStatusRequest;
use Modules\Customer\Http\Requests\ListCustomerRequest;
use Modules\Customer\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Http\Requests\StoreCustomerWithRelationsRequest;
use Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Http\Resources\CustomerResource;
use Modules\Customer\Http\Resources\CustomerSummaryResource;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerBlockerService;
use Modules\Customer\Services\CustomerCreationService;
use Modules\Customer\Services\CustomerQueryService;
use Modules\Customer\Services\CustomerStatusService;
use Modules\Customer\Services\CustomerUpdateService;

final class CustomerController
{
    public function __construct(
        private readonly CustomerQueryService $queries,
        private readonly CustomerCreationService $creation,
        private readonly CustomerUpdateService $updates,
        private readonly CustomerStatusService $statuses,
        private readonly CustomerAuthorizationService $authorization,
        private readonly CustomerBlockerService $blockers,
    ) {}

    public function index(ListCustomerRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request, CustomerAuthorizationService::VIEW);

        return CustomerSummaryResource::collection($this->queries->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize($request, CustomerAuthorizationService::CREATE);

        return $this->created($this->creation->create($request->toData()));
    }

    public function storeWithRelations(StoreCustomerWithRelationsRequest $request): JsonResponse
    {
        $this->authorize($request, CustomerAuthorizationService::CREATE);

        return $this->created($this->creation->create($request->toData()));
    }

    public function show(ListCustomerRequest $request, int $customer): CustomerResource
    {
        $this->authorize($request, CustomerAuthorizationService::VIEW);

        return new CustomerResource($this->queries->find(
            $customer,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateCustomerRequest $request, int $customer): CustomerResource
    {
        $this->authorize($request, CustomerAuthorizationService::UPDATE);

        return new CustomerResource($this->updates->update(
            $this->queries->customer($customer, $request->tenantId(), $request->organizationUnitId()),
            $request->toData(),
        )->load(['defaultCurrency']));
    }

    public function destroy(ListCustomerRequest $request, int $customer): JsonResponse
    {
        $this->authorize($request, CustomerAuthorizationService::DELETE);
        $this->blockers->delete($this->queries->customer(
            $customer,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function activate(ListCustomerRequest $request, int $customer): CustomerResource
    {
        $this->authorize($request, CustomerAuthorizationService::UPDATE);

        return $this->changeTo($request, $customer, CustomerStatus::Active);
    }

    public function deactivate(ListCustomerRequest $request, int $customer): CustomerResource
    {
        $this->authorize($request, CustomerAuthorizationService::UPDATE);

        return $this->changeTo($request, $customer, CustomerStatus::Inactive);
    }

    public function changeStatus(ChangeCustomerStatusRequest $request, int $customer): CustomerResource
    {
        $this->authorize($request, CustomerAuthorizationService::UPDATE);
        $model = $this->queries->customer($customer, $request->tenantId(), $request->organizationUnitId());

        return new CustomerResource($this->statuses->change($model, $request->toData())->load('defaultCurrency'));
    }

    public function lookup(ListCustomerRequest $request, ?string $kind = null): AnonymousResourceCollection
    {
        $this->authorize($request, CustomerAuthorizationService::VIEW);

        return CustomerSummaryResource::collection($this->queries->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
            $kind ?? 'all',
        ));
    }

    private function changeTo(ListCustomerRequest $request, int $customer, CustomerStatus $status): CustomerResource
    {
        $model = $this->queries->customer($customer, $request->tenantId(), $request->organizationUnitId());

        return new CustomerResource($this->statuses->changeTo(
            $model,
            $status,
            $request->currentUserId(),
        )->load('defaultCurrency'));
    }

    private function created(Customer $customer): JsonResponse
    {
        return (new CustomerResource($customer))->response()->setStatusCode(201);
    }

    private function authorize(ListCustomerRequest|StoreCustomerRequest|StoreCustomerWithRelationsRequest|UpdateCustomerRequest|ChangeCustomerStatusRequest $request, string $permission): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
    }
}
