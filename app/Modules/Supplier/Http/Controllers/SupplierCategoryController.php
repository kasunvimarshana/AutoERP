<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierCategoryRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierCategoryRequest;
use Modules\Supplier\Http\Resources\SupplierCategoryResource;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\Supplier\Services\SupplierCategoryService;

final class SupplierCategoryController
{
    public function __construct(private readonly SupplierCategoryService $categories, private readonly SupplierAuthorizationService $authorization) {}

    public function index(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request);

        return SupplierCategoryResource::collection($this->categories->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreSupplierCategoryRequest $request): JsonResponse
    {
        $this->authorize($request);

        return (new SupplierCategoryResource($this->categories->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListSupplierRequest $request, int $supplier_category): SupplierCategoryResource
    {
        $this->authorize($request);

        return new SupplierCategoryResource($this->categories->find(
            $supplier_category,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateSupplierCategoryRequest $request, int $supplier_category): SupplierCategoryResource
    {
        $this->authorize($request);
        $category = $this->categories->find(
            $supplier_category,
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        return new SupplierCategoryResource($this->categories->update($category, $request->toData()));
    }

    public function destroy(ListSupplierRequest $request, int $supplier_category): JsonResponse
    {
        $this->authorize($request);
        $this->categories->delete($this->categories->find(
            $supplier_category,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function lookup(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $this->authorize($request);

        return SupplierCategoryResource::collection($this->categories->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    private function authorize(TenantScopedRequest $request): void
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $request->isMethodSafe() ? SupplierAuthorizationService::VIEW : SupplierAuthorizationService::UPDATE);
    }
}
