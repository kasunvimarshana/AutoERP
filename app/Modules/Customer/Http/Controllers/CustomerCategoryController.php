<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\Http\Requests\ListCustomerRequest;
use Modules\Customer\Http\Requests\StoreCustomerCategoryRequest;
use Modules\Customer\Http\Requests\UpdateCustomerCategoryRequest;
use Modules\Customer\Http\Resources\CustomerCategoryResource;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerCategoryService;

final class CustomerCategoryController
{
    public function __construct(private readonly CustomerCategoryService $categories, private readonly CustomerAuthorizationService $authorization) {}

    public function index(ListCustomerRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request);

        return CustomerCategoryResource::collection($this->categories->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreCustomerCategoryRequest $request): JsonResponse
    {
        $this->authorize($request);

        return (new CustomerCategoryResource($this->categories->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListCustomerRequest $request, int $customer_category): CustomerCategoryResource
    {
        $this->authorize($request);

        return new CustomerCategoryResource($this->categories->find(
            $customer_category,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateCustomerCategoryRequest $request, int $customer_category): CustomerCategoryResource
    {
        $this->authorize($request);
        $category = $this->categories->find(
            $customer_category,
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        return new CustomerCategoryResource($this->categories->update($category, $request->toData()));
    }

    public function destroy(ListCustomerRequest $request, int $customer_category): JsonResponse
    {
        $this->authorize($request);
        $this->categories->delete($this->categories->find(
            $customer_category,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function lookup(ListCustomerRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request);

        return CustomerCategoryResource::collection($this->categories->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    private function authorize(TenantScopedRequest $request): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $request->isMethodSafe() ? CustomerAuthorizationService::VIEW : CustomerAuthorizationService::UPDATE);
    }
}
