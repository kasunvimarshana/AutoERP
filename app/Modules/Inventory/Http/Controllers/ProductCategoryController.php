<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function __construct(
        private ProductCategoryService $service
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/product-categories",
     *     tags={"Product Categories"},
     *     summary="List all product categories",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of product categories"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $categories = $this->service->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/product-categories",
     *     tags={"Product Categories"},
     *     summary="Create a new product category",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "slug"},
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="slug", type="string", example="electronics"),
     *             @OA\Property(property="description", type="string", example="Electronic products"),
     *             @OA\Property(property="parent_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        $tenantId = $request->user()->tenant_id;
        $validated['tenant_id'] = $tenantId;

        $category = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/product-categories/{id}",
     *     tags={"Product Categories"},
     *     summary="Get product category details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $category = $this->service->findById((int)$id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/product-categories/{id}",
     *     tags={"Product Categories"},
     *     summary="Update product category",
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
     *             @OA\Property(property="name", type="string", example="Electronics"),
     *             @OA\Property(property="slug", type="string", example="electronics"),
     *             @OA\Property(property="description", type="string", example="Electronic products")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        $category = $this->service->update((int)$id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/product-categories/{id}",
     *     tags={"Product Categories"},
     *     summary="Delete product category",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete((int)$id);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}
