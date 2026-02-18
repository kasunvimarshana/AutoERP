<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Repositories\ProductRepository;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product API Controller
 *
 * Handles HTTP requests for product management.
 */
class ProductController extends BaseController
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $products = $this->productRepository->paginate($perPage);

        return $this->successResponse($products, 'Products retrieved successfully');
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string|max:255',
            'type' => 'required|in:inventory,service,combo,bundle',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode',
            'buying_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'track_inventory' => 'boolean',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
            'track_expiry' => 'boolean',
            'reorder_level' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $product = $this->productRepository->create($validated);

        return $this->createdResponse($product, 'Product created successfully');
    }

    /**
     * Display the specified product
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productRepository->findOrFail($id);

        // Include current stock information
        $product->current_stock = $this->inventoryService->getCurrentStock($id);

        return $this->successResponse($product, 'Product retrieved successfully');
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'string|unique:products,sku,'.$id,
            'name' => 'string|max:255',
            'type' => 'in:inventory,service,combo,bundle',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode,'.$id,
            'buying_price' => 'numeric|min:0',
            'selling_price' => 'numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'track_inventory' => 'boolean',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
            'track_expiry' => 'boolean',
            'reorder_level' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $this->productRepository->update($id, $validated);
        $product = $this->productRepository->findOrFail($id);

        return $this->successResponse($product, 'Product updated successfully');
    }

    /**
     * Remove the specified product
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productRepository->delete($id);

        return $this->successResponse(null, 'Product deleted successfully');
    }

    /**
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $products = $this->productRepository->search($search);

        return $this->successResponse($products, 'Search results retrieved successfully');
    }

    /**
     * Get products below reorder level
     */
    public function belowReorderLevel(): JsonResponse
    {
        $products = $this->inventoryService->getProductsBelowReorderLevel();

        return $this->successResponse($products, 'Products below reorder level retrieved successfully');
    }

    /**
     * Get stock history for a product
     */
    public function stockHistory(int $id): JsonResponse
    {
        $history = $this->inventoryService->getStockHistory($id);

        return $this->successResponse($history, 'Stock history retrieved successfully');
    }
}
