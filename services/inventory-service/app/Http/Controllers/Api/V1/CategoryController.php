<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Resources\Category\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId   = $request->attributes->get('tenant_id');
        $categories = $this->categoryService->list($tenantId, $request->all());
        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $tree     = $this->categoryService->getTree($tenantId);
        return response()->json(['data' => CategoryResource::collection($tree)]);
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $category = $this->categoryService->create($tenantId, $request->validated());
        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $category = $this->categoryService->findById($tenantId, $id);
        return (new CategoryResource($category))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $category = $this->categoryService->update($tenantId, $id, $request->all());
        return (new CategoryResource($category))->response();
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $this->categoryService->delete($tenantId, $id);
        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
