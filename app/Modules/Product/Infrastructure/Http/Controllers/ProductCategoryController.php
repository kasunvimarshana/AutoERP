<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\FindProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Infrastructure\Http\Requests\ListProductCategoryRequest;
use Modules\Product\Infrastructure\Http\Requests\StoreProductCategoryRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductCategoryRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductCategoryCollection;
use Modules\Product\Infrastructure\Http\Resources\ProductCategoryResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductCategoryController extends AuthorizedController
{
    public function __construct(
        protected FileStorageServiceInterface $storage,
        protected CreateProductCategoryServiceInterface $createProductCategoryService,
        protected UpdateProductCategoryServiceInterface $updateProductCategoryService,
        protected DeleteProductCategoryServiceInterface $deleteProductCategoryService,
        protected FindProductCategoryServiceInterface $findProductCategoryService,
    ) {}

    public function index(ListProductCategoryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ProductCategory::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'slug' => $validated['slug'] ?? null,
            'code' => $validated['code'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $productCategories = $this->findProductCategoryService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ProductCategoryCollection($productCategories))->response();
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', ProductCategory::class);

        $payload = $request->validated();

        if ($request->hasFile('image_path')) {
            $payload['image_path'] = $this->storeImage(
                $request->file('image_path'),
                (int) $payload['tenant_id'],
                'product-categories'
            );
        }

        $productCategory = $this->createProductCategoryService->execute($payload);

        return (new ProductCategoryResource($productCategory))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $productCategory): ProductCategoryResource
    {
        $foundProductCategory = $this->findProductCategoryOrFail($productCategory);
        $this->authorize('view', $foundProductCategory);

        return new ProductCategoryResource($foundProductCategory);
    }

    public function update(UpdateProductCategoryRequest $request, int $productCategory): ProductCategoryResource
    {
        $foundProductCategory = $this->findProductCategoryOrFail($productCategory);
        $this->authorize('update', $foundProductCategory);

        $payload = $request->validated();
        $oldImagePath = $foundProductCategory->getImagePath();
        $newImagePath = null;

        if ($request->hasFile('image_path')) {
            $newImagePath = $this->storeImage(
                $request->file('image_path'),
                (int) $payload['tenant_id'],
                'product-categories'
            );

            $payload['image_path'] = $newImagePath;
        }

        $payload['id'] = $productCategory;

        try {
            $updatedProductCategory = $this->updateProductCategoryService->execute($payload);
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                $this->deleteImageIfSafe($newImagePath, (int) $payload['tenant_id'], 'product-categories');
            }

            throw $exception;
        }

        if ($newImagePath !== null) {
            $this->deleteImageIfSafe($oldImagePath, (int) $payload['tenant_id'], 'product-categories', $newImagePath);
        }

        return new ProductCategoryResource($updatedProductCategory);
    }

    public function destroy(int $productCategory): JsonResponse
    {
        $foundProductCategory = $this->findProductCategoryOrFail($productCategory);
        $this->authorize('delete', $foundProductCategory);

        $this->deleteProductCategoryService->execute(['id' => $productCategory]);

        return Response::json(['message' => 'Product category deleted successfully']);
    }

    private function findProductCategoryOrFail(int $productCategoryId): ProductCategory
    {
        $productCategory = $this->findProductCategoryService->find($productCategoryId);

        if (! $productCategory) {
            throw new NotFoundHttpException('Product category not found.');
        }

        return $productCategory;
    }

    private function storeImage(UploadedFile $image, int $tenantId, string $baseDirectory): string
    {
        return $this->storage->storeFile($image, "{$baseDirectory}/{$tenantId}");
    }

    private function deleteImageIfSafe(?string $imagePath, int $tenantId, string $baseDirectory, ?string $excludePath = null): void
    {
        if ($imagePath === null || $imagePath === '' || $imagePath === $excludePath) {
            return;
        }

        $expectedPrefix = "{$baseDirectory}/{$tenantId}/";

        if (! str_starts_with($imagePath, $expectedPrefix)) {
            return;
        }

        if ($this->storage->exists($imagePath)) {
            $this->storage->delete($imagePath);
        }
    }
}
