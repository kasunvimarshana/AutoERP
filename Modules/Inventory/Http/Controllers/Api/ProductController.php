<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\StoreProductRequest;
use Modules\Inventory\Http\Requests\UpdateProductRequest;
use Modules\Inventory\Http\Resources\ProductResource;
use Modules\Inventory\Services\ProductService;

/**
 * Product API Controller
 *
 * Handles API requests for product management.
 */
class ProductController extends Controller
{
    /**
     * ProductController constructor.
     */
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $products = $this->productService->getPaginated($perPage);

        return ProductResource::collection($products)
            ->response();
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        return (new ProductResource($product))->response();
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated());

        return (new ProductResource($product))->response();
    }

    /**
     * Remove the specified product.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->productService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }

    /**
     * Search products.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $products = $this->productService->search($query);

        return ProductResource::collection($products)
            ->response();
    }

    /**
     * Get products by category.
     */
    public function byCategory(int $categoryId): JsonResponse
    {
        $products = $this->productService->getByCategory($categoryId);

        return ProductResource::collection($products)
            ->response();
    }
}
