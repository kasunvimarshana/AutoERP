<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeServiceInterface;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Infrastructure\Http\Requests\StoreProductAttributeRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductAttributeRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductAttributeResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductAttributeController extends AuthorizedController
{
    public function __construct(
        private readonly CreateProductAttributeServiceInterface $createService,
        private readonly UpdateProductAttributeServiceInterface $updateService,
        private readonly DeleteProductAttributeServiceInterface $deleteService,
        private readonly FindProductAttributeServiceInterface $findService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductAttribute::class);

        $validated = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'group_id' => 'nullable|integer|min:1',
            'type' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ]);

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'group_id' => $validated['group_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'name' => $validated['name'] ?? null,
            'is_required' => $validated['is_required'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $attributes = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return ProductAttributeResource::collection($attributes)->response();
    }

    public function store(StoreProductAttributeRequest $request): JsonResponse
    {
        $this->authorize('create', ProductAttribute::class);
        $payload = $request->validated();

        $attribute = $this->createService->execute($payload);

        return (new ProductAttributeResource($attribute))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $productAttribute): ProductAttributeResource
    {
        $attribute = $this->findProductAttributeOrFail($productAttribute);
        $this->authorize('view', $attribute);

        return new ProductAttributeResource($attribute);
    }

    public function update(UpdateProductAttributeRequest $request, int $productAttribute): ProductAttributeResource
    {
        $attribute = $this->findProductAttributeOrFail($productAttribute);
        $this->authorize('update', $attribute);

        $payload = $request->validated();
        $payload['id'] = $productAttribute;

        return new ProductAttributeResource($this->updateService->execute($payload));
    }

    public function destroy(int $productAttribute): JsonResponse
    {
        $attribute = $this->findProductAttributeOrFail($productAttribute);
        $this->authorize('delete', $attribute);

        $this->deleteService->execute(['id' => $productAttribute]);

        return Response::json(['message' => 'Product attribute deleted successfully']);
    }

    private function findProductAttributeOrFail(int $id): ProductAttribute
    {
        $attribute = $this->findService->find($id);

        if (! $attribute instanceof ProductAttribute) {
            throw new NotFoundHttpException('Product attribute not found.');
        }

        return $attribute;
    }
}
