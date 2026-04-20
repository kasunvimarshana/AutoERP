<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Infrastructure\Http\Requests\ListProductVariantRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductVariantRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductVariantRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductVariantCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductVariantResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductVariantController extends AuthorizedController
{
    public function __construct(
        protected CreateProductVariantServiceInterface $createProductVariantService,
        protected UpdateProductVariantServiceInterface $updateProductVariantService,
        protected DeleteProductVariantServiceInterface $deleteProductVariantService,
        protected FindProductVariantServiceInterface $findProductVariantService,
    ) {}

    public function index(ListProductVariantRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ProductVariant::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'is_default' => $validated['is_default'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $productVariants = $this->findProductVariantService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
            include: $validated['include'] ?? null,
        );

        return (new ProductVariantCollection($productVariants))->response();
    }

    public function store(StoreProductVariantRequest $request): JsonResponse
    {
        $this->authorize('create', ProductVariant::class);

        $productVariant = $this->createProductVariantService->execute($request->validated());

        return (new ProductVariantResource($productVariant))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $productVariant): ProductVariantResource
    {
        $foundProductVariant = $this->findProductVariantOrFail($productVariant);
        $this->authorize('view', $foundProductVariant);

        return new ProductVariantResource($foundProductVariant);
    }

    public function update(UpdateProductVariantRequest $request, int $productVariant): ProductVariantResource
    {
        $foundProductVariant = $this->findProductVariantOrFail($productVariant);
        $this->authorize('update', $foundProductVariant);

        $payload = $request->validated();
        $payload['id'] = $productVariant;

        return new ProductVariantResource($this->updateProductVariantService->execute($payload));
    }

    public function destroy(int $productVariant): JsonResponse
    {
        $foundProductVariant = $this->findProductVariantOrFail($productVariant);
        $this->authorize('delete', $foundProductVariant);

        $this->deleteProductVariantService->execute(['id' => $productVariant]);

        return Response::json(['message' => 'Product variant deleted successfully']);
    }

    private function findProductVariantOrFail(int $productVariantId): ProductVariant
    {
        $productVariant = $this->findProductVariantService->find($productVariantId);

        if (! $productVariant) {
            throw new NotFoundHttpException('Product variant not found.');
        }

        return $productVariant;
    }
}
