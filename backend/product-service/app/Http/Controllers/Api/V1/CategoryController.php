<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->list(
            $request->only(['search', 'parent_id', 'is_active', 'sort_by', 'sort_dir']),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(CategoryResource::collection($categories)->response()->getData(true));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Category::class);

        $data = array_merge($request->validated(), ['tenant_id' => app('current_tenant_id')]);
        $category = $this->categoryService->create($data);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        return response()->json(['data' => new CategoryResource($category)]);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        $this->authorize('update', $category);

        $updated = $this->categoryService->update($id, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        $this->authorize('delete', $category);

        $this->categoryService->delete($id);

        return response()->json(['message' => 'Category deleted successfully.'], 204);
    }
}
