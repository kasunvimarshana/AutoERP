<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ProductService;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inventory Controller
 *
 * @OA\Tag(name="Inventory", description="Inventory management endpoints")
 */
class InventoryController extends Controller
{
    protected ProductService $productService;

    protected StockService $stockService;

    protected StockMovementService $stockMovementService;

    public function __construct(
        ProductService $productService,
        StockService $stockService,
        StockMovementService $stockMovementService
    ) {
        $this->productService = $productService;
        $this->stockService = $stockService;
        $this->stockMovementService = $stockMovementService;
    }

    public function products(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $products = $this->productService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $product = $this->productService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showProduct(int $id): JsonResponse
    {
        try {
            $product = $this->productService->find($id);

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:100|unique:products,sku,'.$id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'unit_price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        try {
            $result = $this->productService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyProduct(int $id): JsonResponse
    {
        try {
            $result = $this->productService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function stock(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $stock = $this->stockService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $stock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function adjustStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer',
            'type' => 'required|string|in:adjustment,restock,damage',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->adjustStock(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function movements(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $movements = $this->stockMovementService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $movements,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|string|in:in,out,transfer,adjustment',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $movement = $this->stockMovementService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Stock movement recorded successfully',
                'data' => $movement,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record stock movement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
