<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Core\Infrastructure\Http\Traits\HasImageStorage;
use Modules\Product\Application\Contracts\ArchiveProductServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\DiscontinueProductServiceInterface;
use Modules\Product\Application\Contracts\DraftProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\PublishProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Infrastructure\Http\Requests\ListProductRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends AuthorizedController
{
    use HasImageStorage;

    public function __construct(
        protected FileStorageServiceInterface $storage,
        protected CreateProductServiceInterface $createProductService,
        protected UpdateProductServiceInterface $updateProductService,
        protected DeleteProductServiceInterface $deleteProductService,
        protected FindProductServiceInterface $findProductService,
        protected PublishProductServiceInterface $publishProductService,
        protected ArchiveProductServiceInterface $archiveProductService,
        protected DiscontinueProductServiceInterface $discontinueProductService,
        protected DraftProductServiceInterface $draftProductService,
    ) {}

    public function index(ListProductRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'org_unit_id' => $validated['org_unit_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'name' => $validated['name'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $products = $this->findProductService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
            include: $validated['include'] ?? null,
        );

        return (new ProductCollection($products))->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $payload = $request->validated();

        if ($request->hasFile('image_path')) {
            $payload['image_path'] = $this->storeImage(
                $request->file('image_path'),
                (int) $payload['tenant_id'],
                'products'
            );
        }

        $product = $this->createProductService->execute($payload);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $product): ProductResource
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('view', $foundProduct);

        return new ProductResource($foundProduct);
    }

    public function update(UpdateProductRequest $request, int $product): ProductResource
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('update', $foundProduct);

        $payload = $request->validated();
        $oldImagePath = $foundProduct->getImagePath();
        $newImagePath = null;

        if ($request->hasFile('image_path')) {
            $newImagePath = $this->storeImage(
                $request->file('image_path'),
                (int) $payload['tenant_id'],
                'products'
            );

            $payload['image_path'] = $newImagePath;
        }

        $payload['id'] = $product;

        try {
            $updatedProduct = $this->updateProductService->execute($payload);
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                $this->deleteImageIfSafe($newImagePath, (int) $payload['tenant_id'], 'products');
            }

            throw $exception;
        }

        if ($newImagePath !== null) {
            $this->deleteImageIfSafe($oldImagePath, (int) $payload['tenant_id'], 'products', $newImagePath);
        }

        return new ProductResource($updatedProduct);
    }

    public function destroy(int $product): JsonResponse
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('delete', $foundProduct);

        $this->deleteProductService->execute(['id' => $product]);

        return Response::json(['message' => 'Product deleted successfully']);
    }

    public function publish(int $product): ProductResource
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('update', $foundProduct);

        return new ProductResource($this->publishProductService->execute(['id' => $product]));
    }

    public function archive(int $product): ProductResource
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('update', $foundProduct);

        return new ProductResource($this->archiveProductService->execute(['id' => $product]));
    }

    public function discontinue(int $product): ProductResource
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('update', $foundProduct);

        return new ProductResource($this->discontinueProductService->execute(['id' => $product]));
    }

    public function draft(int $product): ProductResource
    {
        $foundProduct = $this->findProductOrFail($product);
        $this->authorize('update', $foundProduct);

        return new ProductResource($this->draftProductService->execute(['id' => $product]));
    }

    private function findProductOrFail(int $productId): Product
    {
        $product = $this->findProductService->find($productId);

        if (! $product) {
            throw new NotFoundHttpException('Product not found.');
        }

        return $product;
    }
}
