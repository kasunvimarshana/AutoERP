<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\StoreProductCategoryRequest;
use Modules\Inventory\Http\Requests\UpdateProductCategoryRequest;
use Modules\Inventory\Http\Resources\ProductCategoryResource;
use Modules\Inventory\Repositories\Contracts\ProductCategoryRepositoryInterface;

/**
 * Product Category API Controller
 */
class ProductCategoryController extends Controller
{
    public function __construct(
        protected ProductCategoryRepositoryInterface $categoryRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $categories = $this->categoryRepository->paginate($perPage);

        return ProductCategoryResource::collection($categories)->response();
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryRepository->create($request->validated());

        return (new ProductCategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryRepository->findById($id);

        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return (new ProductCategoryResource($category))->response();
    }

    public function update(UpdateProductCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryRepository->update($id, $request->validated());

        return (new ProductCategoryResource($category))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->categoryRepository->delete($id);

        if (! $deleted) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    public function tree(): JsonResponse
    {
        $tree = $this->categoryRepository->getCategoryTree();

        return ProductCategoryResource::collection($tree)->response();
    }
}
