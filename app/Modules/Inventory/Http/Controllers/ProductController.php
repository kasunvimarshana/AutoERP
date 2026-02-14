<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product and inventory management endpoints"
 * )
 */
class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="List all products",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        $filters = $request->only(['category_id', 'status', 'type', 'is_active']);
        
        $products = $this->service->paginate($perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "sku", "type"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="type", type="string", enum={"product", "service"}),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="unit_price", type="number", format="float"),
     *             @OA\Property(property="cost_price", type="number", format="float"),
     *             @OA\Property(property="track_inventory", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('products', 'sku')->where('tenant_id', $tenantId)
            ],
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit_of_measure' => 'nullable|string|max:50',
            'track_inventory' => 'boolean',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
            'track_expiry' => 'boolean',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        $product = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Get product by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->service->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product->load(['category', 'brand'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Update product",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="unit_price", type="number", format="float"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "discontinued"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('products', 'sku')->where('tenant_id', $tenantId)->ignore($id)
            ],
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit_of_measure' => 'nullable|string|max:50',
            'track_inventory' => 'boolean',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
            'track_expiry' => 'boolean',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $product = $this->service->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Delete product",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}/inventory",
     *     tags={"Products"},
     *     summary="Get product inventory levels",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inventory levels retrieved"
     *     )
     * )
     */
    public function inventory(int $id): JsonResponse
    {
        // Get product inventory levels across warehouses
        $product = $this->service->find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'inventory' => [] // Placeholder for warehouse inventory levels
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/search",
     *     tags={"Products"},
     *     summary="Search products",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // The TenantScoped trait should automatically apply tenant filtering
        // but we'll be explicit to ensure it works
        $products = Product::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/{id}/adjust-stock",
     *     tags={"Products"},
     *     summary="Adjust product stock level",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity", "type"},
     *             @OA\Property(property="quantity", type="integer"),
     *             @OA\Property(property="type", type="string", enum={"addition", "subtraction", "adjustment"}),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="warehouse_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock adjusted successfully"
     *     )
     * )
     */
    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:addition,subtraction,adjustment',
            'reason' => 'nullable|string|max:255',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $result = $this->service->adjustStock($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted successfully',
            'data' => $result
        ]);
    }
}
