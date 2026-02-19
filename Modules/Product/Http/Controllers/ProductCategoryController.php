<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Requests\StoreCategoryRequest;
use Modules\Product\Http\Requests\UpdateCategoryRequest;
use Modules\Product\Http\Resources\ProductCategoryResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Services\ProductCategoryService;

/**
 * Product Category Controller
 */
class ProductCategoryController extends Controller
{
    public function __construct(
        private ProductCategoryService $categoryService
    ) {}

    /**
     * Display a listing of product categories.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProductCategory::class);

        $query = ProductCategory::query()->with(['parent', 'children']);

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $categories = $query->paginate($perPage);

        return response()->json([
            'data' => ProductCategoryResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', ProductCategory::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $category = $this->categoryService->createCategory($data);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => new ProductCategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('view', $productCategory);

        $productCategory->load(['parent', 'children']);

        return response()->json([
            'data' => new ProductCategoryResource($productCategory),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($productCategory, $data) {
            $productCategory->update($data);
        });

        $productCategory->load(['parent', 'children']);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => new ProductCategoryResource($productCategory),
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('delete', $productCategory);

        if ($productCategory->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with child categories.',
            ], 400);
        }

        if ($productCategory->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with products.',
            ], 400);
        }

        DB::transaction(function () use ($productCategory) {
            $productCategory->delete();
        });

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Get child categories of the specified category.
     */
    public function getChildren(ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('view', $productCategory);

        $children = $productCategory->children()
            ->with(['children'])
            ->get();

        return response()->json([
            'data' => ProductCategoryResource::collection($children),
        ]);
    }

    /**
     * Get products in the specified category.
     */
    public function getProducts(Request $request, ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('view', $productCategory);

        $query = $productCategory->products()
            ->with(['category', 'buyingUnit', 'sellingUnit']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
