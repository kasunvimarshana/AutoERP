<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\Http\Requests\ListCustomerRequest;
use Modules\Customer\Http\Requests\StoreCustomerCategoryRequest;
use Modules\Customer\Http\Requests\UpdateCustomerCategoryRequest;
use Modules\Customer\Http\Resources\CustomerCategoryResource;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerCategoryService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
        $expectedVersion = $request->filled('expected_version') ? (int) $request->input('expected_version') : null;

        $updated = DB::transaction(function () use ($request, $customer_category, $expectedVersion) {
            $category = $this->categories->find(
                $customer_category,
                $request->tenantId(),
                $request->organizationUnitId(),
            );
            $category = $category->newQuery()->lockForUpdate()->findOrFail($category->getKey());

            if ($expectedVersion !== null && (int) $category->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Customer category was changed by someone else. Reload before saving.');
            }

            $nextVersion = ((int) $category->row_version) + 1;
            $saved = $this->categories->update($category, $request->toData());
            $saved->forceFill(['row_version' => $nextVersion])->save();

            return $saved;
        });

        return new CustomerCategoryResource($updated);
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
