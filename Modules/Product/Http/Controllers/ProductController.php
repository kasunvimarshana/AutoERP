<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Product\Enums\ProductType;
use Modules\Product\Http\Requests\AddBundleItemRequest;
use Modules\Product\Http\Requests\AddCompositeItemRequest;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductBundleResource;
use Modules\Product\Http\Resources\ProductCompositeResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductBundle;
use Modules\Product\Models\ProductComposite;
use Modules\Product\Services\ProductService;

/**
 * Product Controller
 *
 * Handles HTTP requests for product management.
 * Uses ProductService for business logic following Clean Architecture.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $type = $request->has('type') ? ProductType::from($request->type) : null;
        $categoryId = $request->get('category_id');
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : null;
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 15);

        $products = $this->productService->getPaginatedProducts(
            type: $type,
            categoryId: $categoryId,
            isActive: $isActive,
            search: $search,
            perPage: $perPage
        );

        return ApiResponse::paginated(
            $products->setCollection(
                $products->getCollection()->map(fn ($product) => new ProductResource($product))
            ),
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenantId = $request->user()->currentTenant()->id;

        $product = $this->productService->createProduct($data, $tenantId);

        return ApiResponse::success(
            new ProductResource($product),
            'Product created successfully',
            201
        );
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product = $this->productService->getProductById($product->id);

        return ApiResponse::success(
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();
        $product = $this->productService->updateProduct($product, $data);

        return ApiResponse::success(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->productService->deleteProduct($product);

        return ApiResponse::success(
            null,
            'Product deleted successfully'
        );
    }

    /**
     * List bundle items for a product.
     */
    public function getBundleItems(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        try {
            $bundleItems = $this->productService->getBundleItems($product);

            return ApiResponse::success(
                ProductBundleResource::collection($bundleItems),
                'Bundle items retrieved successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Add a bundle item to a product.
     */
    public function addBundleItem(AddBundleItemRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();
        $tenantId = $request->user()->currentTenant()->id;

        try {
            $bundleItem = $this->productService->addBundleItem($product, $data, $tenantId);

            return ApiResponse::success(
                new ProductBundleResource($bundleItem),
                'Bundle item added successfully',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Remove a bundle item from a product.
     */
    public function removeBundleItem(Product $product, ProductBundle $bundleItem): JsonResponse
    {
        $this->authorize('update', $product);

        try {
            $this->productService->removeBundleItem($product, $bundleItem);

            return ApiResponse::success(
                null,
                'Bundle item removed successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * List composite parts for a product.
     */
    public function getCompositeParts(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        try {
            $compositeParts = $this->productService->getCompositeParts($product);

            return ApiResponse::success(
                ProductCompositeResource::collection($compositeParts),
                'Composite parts retrieved successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Add a composite part to a product.
     */
    public function addCompositePart(AddCompositeItemRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();
        $tenantId = $request->user()->currentTenant()->id;

        try {
            $compositePart = $this->productService->addCompositePart($product, $data, $tenantId);

            return ApiResponse::success(
                new ProductCompositeResource($compositePart),
                'Composite part added successfully',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Remove a composite part from a product.
     */
    public function removeCompositePart(Product $product, ProductComposite $compositePart): JsonResponse
    {
        $this->authorize('update', $product);

        try {
            $this->productService->removeCompositePart($product, $compositePart);

            return ApiResponse::success(
                null,
                'Composite part removed successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
