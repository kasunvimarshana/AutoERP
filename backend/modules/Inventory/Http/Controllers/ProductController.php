<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Inventory\Http\Requests\StoreProductRequest;
use Modules\Inventory\Http\Requests\UpdateProductRequest;
use Modules\Inventory\Http\Resources\ProductResource;
use Modules\Inventory\Services\ProductService;

/**
 * Product Controller
 *
 * Handles HTTP requests for product management.
 */
class ProductController extends BaseController
{
    /**
     * Constructor
     */
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/inventory/products",
     *     summary="List all products",
     *     description="Retrieve paginated list of all products with filtering, sorting, and search capabilities",
     *     operationId="productsIndex",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by product name, SKU, or description",
     *         required=false,
     *         @OA\Schema(type="string", example="laptop")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001")
     *     ),
     *     @OA\Parameter(
     *         name="product_type",
     *         in="query",
     *         description="Filter by product type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"inventory", "service", "bundle", "composite"}, example="inventory")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by product status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft", "active", "inactive", "discontinued"}, example="active")
     *     ),
     *     @OA\Parameter(
     *         name="track_inventory",
     *         in="query",
     *         description="Filter by inventory tracking status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "sku", "created_at", "updated_at", "selling_price"}, default="name")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://api.example.com/api/inventory/products?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://api.example.com/api/inventory/products?page=10"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://api.example.com/api/inventory/products?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=150)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'category_id' => $request->input('category_id'),
                'product_type' => $request->input('product_type'),
                'status' => $request->input('status'),
                'track_inventory' => $request->boolean('track_inventory'),
                'sort_by' => $request->input('sort_by', 'name'),
                'sort_order' => $request->input('sort_order', 'asc'),
                'per_page' => $request->integer('per_page', 15),
            ];

            $products = $this->productService->getAll($filters);

            return $this->success(ProductResource::collection($products));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch products: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/products",
     *     summary="Create a new product",
     *     description="Create a new product with variants and attributes",
     *     operationId="productsStore",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product creation data",
     *         @OA\JsonContent(ref="#/components/schemas/StoreProductRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->create($request->validated());

            return $this->created(ProductResource::make($product), 'Product created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create product: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/products/{id}",
     *     summary="Get product details",
     *     description="Retrieve detailed information about a specific product including variants and stock info",
     *     operationId="productsShow",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productService->getById($id);

            return $this->success(ProductResource::make($product));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Product not found');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch product: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/inventory/products/{id}",
     *     summary="Update a product",
     *     description="Update an existing product's information, variants, and attributes",
     *     operationId="productsUpdate",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product update data",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateProductRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Invalid data",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            $product = $this->productService->update($id, $request->validated());

            return $this->updated(ProductResource::make($product), 'Product updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Product not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update product: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/inventory/products/{id}",
     *     summary="Delete a product",
     *     description="Delete a product from the system. Products with existing stock cannot be deleted.",
     *     operationId="productsDestroy",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID (UUID)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Product has stock or dependencies",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->productService->delete($id);

            return $this->deleted('Product deleted successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Product not found');
        } catch (\Exception $e) {
            return $this->error('Failed to delete product: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/products/by-sku",
     *     summary="Get product by SKU",
     *     description="Retrieve a product by its SKU code",
     *     operationId="productsGetBySKU",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="sku",
     *         in="query",
     *         description="Product SKU",
     *         required=true,
     *         @OA\Schema(type="string", maxLength=100, example="PRD-001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - SKU parameter is missing or invalid",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function getBySKU(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sku' => 'required|string|max:100',
            ]);

            $sku = $request->input('sku');
            $product = $this->productService->getBySKU($sku);

            if (! $product) {
                return $this->notFound('Product not found with SKU: '.$sku);
            }

            return $this->success($product);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to fetch product: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/products/low-stock",
     *     summary="Get low stock products",
     *     description="Retrieve all products with stock levels below their reorder level",
     *     operationId="productsLowStock",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Low stock products retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     allOf={
     *                         @OA\Schema(ref="#/components/schemas/Product"),
     *                         @OA\Schema(
     *                             type="object",
     *                             @OA\Property(property="current_stock", type="number", format="decimal", example=5.00, description="Current stock level"),
     *                             @OA\Property(property="reorder_level", type="number", format="decimal", example=10.00, description="Reorder threshold")
     *                         )
     *                     }
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function lowStock(): JsonResponse
    {
        try {
            $products = $this->productService->getLowStockProducts();

            return $this->success($products);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch low stock products: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/products/bulk-import",
     *     summary="Bulk import products",
     *     description="Import multiple products at once. Returns summary of successful and failed imports.",
     *     operationId="productsBulkImport",
     *     tags={"Inventory-Products"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of products to import",
     *         @OA\JsonContent(ref="#/components/schemas/BulkImportRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk import completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bulk import completed"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=100, description="Total number of products submitted"),
     *                 @OA\Property(property="imported", type="integer", example=95, description="Number of products successfully imported"),
     *                 @OA\Property(property="failed", type="integer", example=5, description="Number of products that failed to import"),
     *                 @OA\Property(
     *                     property="errors",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="row", type="integer", example=15),
     *                         @OA\Property(property="sku", type="string", example="PRD-015"),
     *                         @OA\Property(property="error", type="string", example="SKU already exists")
     *                     ),
     *                     description="List of errors for failed imports"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid product data format",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'products' => 'required|array',
                'products.*.sku' => 'nullable|string|max:100',
                'products.*.name' => 'required|string|max:255',
                'products.*.product_type' => 'required|string',
            ]);

            $result = $this->productService->bulkImport($request->input('products'));

            return $this->success($result, 'Bulk import completed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to import products: '.$e->getMessage(), 500);
        }
    }
}
