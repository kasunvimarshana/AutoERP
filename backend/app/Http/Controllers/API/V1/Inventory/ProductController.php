<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Api\ApiController;
use App\Modules\Inventory\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * List all products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $products = $this->productService->paginate($perPage, ['variants', 'batches']);

            return $this->paginatedResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Store a new product
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:20',
            'is_variant_product' => 'nullable|boolean',
            'track_stock' => 'nullable|boolean',
            'track_batch' => 'nullable|boolean',
            'track_serial' => 'nullable|boolean',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_inclusive' => 'nullable|boolean',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'images' => 'nullable|array',
            'attributes' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_purchasable' => 'nullable|boolean',
            'is_sellable' => 'nullable|boolean',
        ]);

        try {
            $result = $this->productService->createProduct($request->all());

            if ($result['success']) {
                return $this->createdResponse($result['data'], $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Show a specific product
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->findById($id, ['variants', 'batches']);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            // Add stock balance
            $product->stock_balance = $product->getStockBalance();

            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update a product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $id,
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:20',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $result = $this->productService->updateProduct($id, $request->all());

            if ($result['success']) {
                return $this->successResponse($result['data'], $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Delete a product
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->productService->deleteProduct($id);

            if ($result['success']) {
                return $this->successResponse(null, $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'term' => 'required|string|min:2',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
            'brand' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $result = $this->productService->searchProducts(
                $request->term,
                $request->except('term')
            );

            return $this->successResponse($result['data'], 'Search completed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get products with low stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');
            $result = $this->productService->getLowStockProducts($branchId);

            return $this->successResponse($result['data'], 'Low stock products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get products needing reorder
     */
    public function needingReorder(Request $request): JsonResponse
    {
        try {
            $branchId = $request->input('branch_id');
            $result = $this->productService->getProductsNeedingReorder($branchId);

            return $this->successResponse($result['data'], 'Products needing reorder retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $result = $this->productService->toggleProductStatus($id);

            if ($result['success']) {
                return $this->successResponse(['is_active' => $result['is_active']], $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Bulk import products
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.name' => 'required|string|max:255',
            'products.*.sku' => 'nullable|string|max:100',
            'products.*.cost_price' => 'nullable|numeric|min:0',
            'products.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $result = $this->productService->bulkImportProducts($request->products);

            return $this->successResponse($result, 'Bulk import completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
