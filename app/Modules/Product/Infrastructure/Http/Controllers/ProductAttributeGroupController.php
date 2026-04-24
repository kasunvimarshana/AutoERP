<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeGroupServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeGroupServiceInterface;
use Modules\Product\Domain\Entities\ProductAttributeGroup;
use Modules\Product\Infrastructure\Http\Requests\StoreProductAttributeGroupRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductAttributeGroupRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductAttributeGroupResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductAttributeGroupController extends AuthorizedController
{
    public function __construct(
        private readonly CreateProductAttributeGroupServiceInterface $createService,
        private readonly UpdateProductAttributeGroupServiceInterface $updateService,
        private readonly DeleteProductAttributeGroupServiceInterface $deleteService,
        private readonly FindProductAttributeGroupServiceInterface $findService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductAttributeGroup::class);

        $validated = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'name' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ]);

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'name' => $validated['name'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $groups = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return ProductAttributeGroupResource::collection($groups)->response();
    }

    public function store(StoreProductAttributeGroupRequest $request): JsonResponse
    {
        $this->authorize('create', ProductAttributeGroup::class);
        $payload = $request->validated();

        $group = $this->createService->execute($payload);

        return (new ProductAttributeGroupResource($group))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $productAttributeGroup): ProductAttributeGroupResource
    {
        $group = $this->findProductAttributeGroupOrFail($productAttributeGroup);
        $this->authorize('view', $group);

        return new ProductAttributeGroupResource($group);
    }

    public function update(UpdateProductAttributeGroupRequest $request, int $productAttributeGroup): ProductAttributeGroupResource
    {
        $group = $this->findProductAttributeGroupOrFail($productAttributeGroup);
        $this->authorize('update', $group);

        $payload = $request->validated();
        $payload['id'] = $productAttributeGroup;

        return new ProductAttributeGroupResource($this->updateService->execute($payload));
    }

    public function destroy(int $productAttributeGroup): JsonResponse
    {
        $group = $this->findProductAttributeGroupOrFail($productAttributeGroup);
        $this->authorize('delete', $group);

        $this->deleteService->execute(['id' => $productAttributeGroup]);

        return Response::json(['message' => 'Product attribute group deleted successfully']);
    }

    private function findProductAttributeGroupOrFail(int $id): ProductAttributeGroup
    {
        $group = $this->findService->find($id);

        if (! $group instanceof ProductAttributeGroup) {
            throw new NotFoundHttpException('Product attribute group not found.');
        }

        return $group;
    }
}
