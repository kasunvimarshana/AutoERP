<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Application\Contracts\FindProductBrandServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductBrandServiceInterface;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Product\Infrastructure\Http\Requests\ListProductBrandRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductBrandRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductBrandRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductBrandCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductBrandResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductBrandController extends AuthorizedController
{
    public function __construct(
        protected CreateProductBrandServiceInterface $createProductBrandService,
        protected UpdateProductBrandServiceInterface $updateProductBrandService,
        protected DeleteProductBrandServiceInterface $deleteProductBrandService,
        protected FindProductBrandServiceInterface $findProductBrandService,
    ) {}

    public function index(ListProductBrandRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ProductBrand::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $productBrands = $this->findProductBrandService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ProductBrandCollection($productBrands))->response();
    }

    public function store(StoreProductBrandRequest $request): JsonResponse
    {
        $this->authorize('create', ProductBrand::class);

        $productBrand = $this->createProductBrandService->execute($request->validated());

        return (new ProductBrandResource($productBrand))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $productBrand): ProductBrandResource
    {
        $foundProductBrand = $this->findProductBrandOrFail($productBrand);
        $this->authorize('view', $foundProductBrand);

        return new ProductBrandResource($foundProductBrand);
    }

    public function update(UpdateProductBrandRequest $request, int $productBrand): ProductBrandResource
    {
        $foundProductBrand = $this->findProductBrandOrFail($productBrand);
        $this->authorize('update', $foundProductBrand);

        $payload = $request->validated();
        $payload['id'] = $productBrand;

        return new ProductBrandResource($this->updateProductBrandService->execute($payload));
    }

    public function destroy(int $productBrand): JsonResponse
    {
        $foundProductBrand = $this->findProductBrandOrFail($productBrand);
        $this->authorize('delete', $foundProductBrand);

        $this->deleteProductBrandService->execute(['id' => $productBrand]);

        return Response::json(['message' => 'Product brand deleted successfully']);
    }

    private function findProductBrandOrFail(int $productBrandId): ProductBrand
    {
        $productBrand = $this->findProductBrandService->find($productBrandId);

        if (! $productBrand) {
            throw new NotFoundHttpException('Product brand not found.');
        }

        return $productBrand;
    }
}
