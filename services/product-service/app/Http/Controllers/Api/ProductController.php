<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Return a paginated, filtered, and sorted list of products.
     */
    public function index(Request $request): ProductCollection|JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'category',
                'status',
                'min_price',
                'max_price',
                'sort_by',
                'sort_direction',
                'per_page',
            ]);

            $products = $this->productService->getAllProducts($filters);

            return new ProductCollection($products);
        } catch (Throwable $e) {
            Log::error('Failed to fetch products', [
                'error'   => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve products.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a single product by ID.
     */
    public function show(int $id): ProductResource|JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);

            if ($product === null) {
                return response()->json(['message' => 'Product not found.'], 404);
            }

            return new ProductResource($product);
        } catch (Throwable $e) {
            Log::error('Failed to fetch product', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to retrieve product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new product.
     */
    public function store(StoreProductRequest $request): ProductResource|JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());

            return (new ProductResource($product))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to create product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing product.
     */
    public function update(UpdateProductRequest $request, int $id): ProductResource|JsonResponse
    {
        try {
            $product = $this->productService->updateProduct($id, $request->validated());

            if ($product === null) {
                return response()->json(['message' => 'Product not found.'], 404);
            }

            return new ProductResource($product);
        } catch (Throwable $e) {
            Log::error('Failed to update product', [
                'id'    => $id,
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to update product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->productService->deleteProduct($id);

            if (! $deleted) {
                return response()->json(['message' => 'Product not found.'], 404);
            }

            return response()->json(['message' => 'Product deleted successfully.'], 200);
        } catch (Throwable $e) {
            Log::error('Failed to delete product', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
