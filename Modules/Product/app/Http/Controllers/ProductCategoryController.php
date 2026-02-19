<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Requests\StoreProductCategoryRequest;
use Modules\Product\Requests\UpdateProductCategoryRequest;
use Modules\Product\Resources\ProductCategoryResource;
use Modules\Product\Services\ProductCategoryService;
use OpenApi\Attributes as OA;

/**
 * Product Category Controller
 *
 * Handles HTTP requests for ProductCategory operations
 */
class ProductCategoryController extends Controller
{
    /**
     * ProductCategoryController constructor
     */
    public function __construct(
        private readonly ProductCategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories
     *
     * @OA\Get(
     *     path="/api/v1/product-categories",
     *     summary="List all product categories",
     *     operationId="getProductCategories",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $categories = $this->categoryService->getAll($filters);

        return $this->successResponse(
            ProductCategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    /**
     * Store a newly created category
     *
     * @OA\Post(
     *     path="/api/v1/product-categories",
     *     summary="Create a new product category",
     *     operationId="createProductCategory",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=201, description="Category created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return $this->createdResponse(
            new ProductCategoryResource($category),
            'Category created successfully'
        );
    }

    /**
     * Display the specified category
     *
     * @OA\Get(
     *     path="/api/v1/product-categories/{id}",
     *     summary="Get category by ID",
     *     operationId="getProductCategoryById",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Category retrieved successfully"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getById($id);

        return $this->successResponse(
            new ProductCategoryResource($category),
            'Category retrieved successfully'
        );
    }

    /**
     * Update the specified category
     *
     * @OA\Put(
     *     path="/api/v1/product-categories/{id}",
     *     summary="Update category",
     *     operationId="updateProductCategory",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Category updated successfully"),
     *     @OA\Response(response=404, description="Category not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProductCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->update($id, $request->validated());

        return $this->successResponse(
            new ProductCategoryResource($category),
            'Category updated successfully'
        );
    }

    /**
     * Remove the specified category
     *
     * @OA\Delete(
     *     path="/api/v1/product-categories/{id}",
     *     summary="Delete category",
     *     operationId="deleteProductCategory",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Category deleted successfully"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->categoryService->delete($id);

        return $this->successResponse(
            null,
            'Category deleted successfully'
        );
    }

    /**
     * Get category tree
     *
     * @OA\Get(
     *     path="/api/v1/product-categories/tree",
     *     summary="Get category tree",
     *     operationId="getProductCategoryTree",
     *     tags={"Product Categories"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Category tree retrieved successfully")
     * )
     */
    public function tree(): JsonResponse
    {
        $tree = $this->categoryService->getCategoryTree();

        return $this->successResponse(
            ProductCategoryResource::collection($tree),
            'Category tree retrieved successfully'
        );
    }
}
