<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\FindProductAttributeValueServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductAttributeValueServiceInterface;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Infrastructure\Http\Requests\StoreProductAttributeValueRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductAttributeValueRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductAttributeValueResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductAttributeValueController extends AuthorizedController
{
    public function __construct(
        private readonly CreateProductAttributeValueServiceInterface $createService,
        private readonly UpdateProductAttributeValueServiceInterface $updateService,
        private readonly DeleteProductAttributeValueServiceInterface $deleteService,
        private readonly FindProductAttributeValueServiceInterface $findService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductAttributeValue::class);

        $validated = $request->validate([
            'tenant_id' => 'nullable|integer|min:1',
            'attribute_id' => 'nullable|integer|min:1',
            'value' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ]);

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'attribute_id' => $validated['attribute_id'] ?? null,
            'value' => $validated['value'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $values = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return ProductAttributeValueResource::collection($values)->response();
    }

    public function store(StoreProductAttributeValueRequest $request): JsonResponse
    {
        $this->authorize('create', ProductAttributeValue::class);
        $payload = $request->validated();

        $value = $this->createService->execute($payload);

        return (new ProductAttributeValueResource($value))->response()->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $productAttributeValue): ProductAttributeValueResource
    {
        $value = $this->findProductAttributeValueOrFail($productAttributeValue);
        $this->authorize('view', $value);

        return new ProductAttributeValueResource($value);
    }

    public function update(UpdateProductAttributeValueRequest $request, int $productAttributeValue): ProductAttributeValueResource
    {
        $value = $this->findProductAttributeValueOrFail($productAttributeValue);
        $this->authorize('update', $value);

        $payload = $request->validated();
        $payload['id'] = $productAttributeValue;

        return new ProductAttributeValueResource($this->updateService->execute($payload));
    }

    public function destroy(int $productAttributeValue): JsonResponse
    {
        $value = $this->findProductAttributeValueOrFail($productAttributeValue);
        $this->authorize('delete', $value);

        $this->deleteService->execute(['id' => $productAttributeValue]);

        return Response::json(['message' => 'Product attribute value deleted successfully']);
    }

    private function findProductAttributeValueOrFail(int $id): ProductAttributeValue
    {
        $value = $this->findService->find($id);

        if (! $value instanceof ProductAttributeValue) {
            throw new NotFoundHttpException('Product attribute value not found.');
        }

        return $value;
    }
}
