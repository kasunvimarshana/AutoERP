<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Requests\StoreProductRequest;
use Modules\Product\Requests\UpdateProductRequest;
use Modules\Product\Resources\ProductResource;
use Modules\Product\Services\ProductService;
use OpenApi\Attributes as OA;

/**
 * Product Controller
 *
 * Handles HTTP requests for Product operations
 * Follows Controller → Service → Repository pattern
 */
class ProductController extends Controller
{
    /**
     * ProductController constructor
     */
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of products
     *
     * @OA\Get(
     *     path="/api/v1/products",
     *     summary="List all products",
     *     description="Get a paginated list of all products with optional filtering",
     *     operationId="getProducts",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="paginate",
     *         in="query",
     *         description="Enable pagination",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Product")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $products = $this->productService->getAll($filters);

        return $this->successResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product
     *
     * @OA\Post(
     *     path="/api/v1/products",
     *     summary="Create a new product",
     *     description="Create a new product with the provided data",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product data",
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="sku", type="string", example="PRD-20260219-0001"),
     *             @OA\Property(property="name", type="string", example="Laptop Computer"),
     *             @OA\Property(property="description", type="string", example="High-performance laptop"),
     *             @OA\Property(property="type", type="string", enum={"goods", "services", "digital", "bundle", "composite"}),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "discontinued", "out_of_stock"}),
     *             @OA\Property(property="cost_price", type="number", example=800.00),
     *             @OA\Property(property="selling_price", type="number", example=1200.00),
     *             @OA\Property(property="track_inventory", type="boolean", example=true),
     *             @OA\Property(property="current_stock", type="integer", example=50)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return $this->createdResponse(
            new ProductResource($product),
            'Product created successfully'
        );
    }

    /**
     * Display the specified product
     *
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     summary="Get product by ID",
     *     description="Retrieve a specific product by its ID",
     *     operationId="getProductById",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getById($id);

        return $this->successResponse(
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified product
     *
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     summary="Update product",
     *     description="Update a product with the provided data",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Product data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="cost_price", type="number"),
     *             @OA\Property(property="selling_price", type="number")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated());

        return $this->successResponse(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified product
     *
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     summary="Delete product",
     *     description="Soft delete a product",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productService->delete($id);

        return $this->successResponse(
            null,
            'Product deleted successfully'
        );
    }
}
