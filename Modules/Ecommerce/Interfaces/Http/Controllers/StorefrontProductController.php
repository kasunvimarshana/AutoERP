<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Application\Commands\CreateStorefrontProductCommand;
use Modules\Ecommerce\Application\Commands\DeleteStorefrontProductCommand;
use Modules\Ecommerce\Application\Commands\UpdateStorefrontProductCommand;
use Modules\Ecommerce\Application\Services\StorefrontProductService;
use Modules\Ecommerce\Interfaces\Http\Requests\CreateStorefrontProductRequest;
use Modules\Ecommerce\Interfaces\Http\Requests\UpdateStorefrontProductRequest;
use Modules\Ecommerce\Interfaces\Http\Resources\StorefrontProductResource;

class StorefrontProductController extends BaseController
{
    public function __construct(
        private readonly StorefrontProductService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAll($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($p) => (new StorefrontProductResource($p))->resolve(),
                $result['items']
            ),
            message: 'Storefront products retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateStorefrontProductRequest $request): JsonResponse
    {
        try {
            $product = $this->service->create(new CreateStorefrontProductCommand(
                tenantId: $request->validated('tenant_id'),
                productId: $request->validated('product_id'),
                slug: $request->validated('slug'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                price: (string) $request->validated('price'),
                currency: $request->validated('currency'),
                isActive: (bool) $request->validated('is_active', true),
                isFeatured: (bool) $request->validated('is_featured', false),
                sortOrder: (int) $request->validated('sort_order', 0),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new StorefrontProductResource($product))->resolve(),
            message: 'Storefront product created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $product = $this->service->findById($id, $tenantId);

        if ($product === null) {
            return $this->error('Storefront product not found', status: 404);
        }

        return $this->success(
            data: (new StorefrontProductResource($product))->resolve(),
            message: 'Storefront product retrieved successfully',
        );
    }

    public function update(UpdateStorefrontProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->service->update(new UpdateStorefrontProductCommand(
                id: $id,
                tenantId: $request->validated('tenant_id'),
                slug: $request->validated('slug'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                price: (string) $request->validated('price'),
                currency: $request->validated('currency'),
                isActive: (bool) $request->validated('is_active', true),
                isFeatured: (bool) $request->validated('is_featured', false),
                sortOrder: (int) $request->validated('sort_order', 0),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new StorefrontProductResource($product))->resolve(),
            message: 'Storefront product updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->delete(new DeleteStorefrontProductCommand($id, $tenantId));

            return $this->success(message: 'Storefront product deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function featured(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $products = $this->service->findFeatured($tenantId);

        return $this->success(
            data: array_map(
                fn ($p) => (new StorefrontProductResource($p))->resolve(),
                $products
            ),
            message: 'Featured storefront products retrieved successfully',
        );
    }
}
