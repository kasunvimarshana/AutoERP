<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Application\Contracts\CategoryServiceInterface;
use Modules\Product\Infrastructure\Http\Resources\CategoryResource;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $categories = $this->categoryService->getAllCategories($tenantId);
        return response()->json(CategoryResource::collection($categories));
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json($this->categoryService->getCategoryTree($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $category = $this->categoryService->createCategory($tenantId, $request->all());
        return response()->json(new CategoryResource($category), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $category = $this->categoryService->getCategory($tenantId, $id);
        return response()->json(new CategoryResource($category));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $category = $this->categoryService->updateCategory($tenantId, $id, $request->all());
        return response()->json(new CategoryResource($category));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->categoryService->deleteCategory($tenantId, $id);
        return response()->json(null, 204);
    }
}
