<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Product;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ProductResource;
use App\Modules\Product\Services\ProductService;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\CalculatePriceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends BaseController
{
    /**
     * @var ProductService
     */
    protected $productService;

    /**
     * ProductController constructor.
     *
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $products = $this->productService->getAll([], $perPage);

            return $this->paginatedResponse(
                ProductResource::collection($products),
                'Products retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve products: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created product.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = $this->productService->create($validated);

            return $this->successResponse(
                new ProductResource($product),
                'Product created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getById($id);

            return $this->successResponse(
                new ProductResource($product),
                'Product retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product: ' . $e->getMessage(), [], 404);
        }
    }

    /**
     * Update the specified product.
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $product = $this->productService->update($id, $validated);

            return $this->successResponse(
                new ProductResource($product),
                'Product updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->delete($id);

            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product: ' . $e->getMessage());
        }
    }

    /**
     * Search products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query', '');
            $filters = $request->only(['category_id', 'type', 'is_active', 'min_price', 'max_price']);

            $products = $this->productService->search($query, $filters);

            return $this->successResponse(
                ProductResource::collection($products),
                'Products searched successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search products: ' . $e->getMessage());
        }
    }

    /**
     * Get low stock products.
     *
     * @return JsonResponse
     */
    public function lowStock(): JsonResponse
    {
        try {
            $products = $this->productService->getLowStockProducts();

            return $this->successResponse(
                ProductResource::collection($products),
                'Low stock products retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve low stock products: ' . $e->getMessage());
        }
    }

    /**
     * Get out of stock products.
     *
     * @return JsonResponse
     */
    public function outOfStock(): JsonResponse
    {
        try {
            $products = $this->productService->getOutOfStockProducts();

            return $this->successResponse(
                ProductResource::collection($products),
                'Out of stock products retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve out of stock products: ' . $e->getMessage());
        }
    }

    /**
     * Calculate final price for a product.
     *
     * @param CalculatePriceRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function calculatePrice(CalculatePriceRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $priceDetails = $this->productService->calculateFinalPrice(
                $id,
                $validated['quantity'],
                $validated['context'] ?? []
            );

            return $this->successResponse($priceDetails, 'Price calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate price: ' . $e->getMessage());
        }
    }
}
