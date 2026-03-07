<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    /**
     * GET /api/v1/categories
     *
     * Supported query params:
     *   filter[name], filter[is_active], filter[parent_id]
     *   sort=name,sort_order
     *   include=children,parent
     */
    public function index(Request $request): CategoryCollection
    {
        $perPage   = min((int) $request->query('per_page', 15), 100);
        $paginator = $this->categoryService->list($perPage);

        return new CategoryCollection($paginator);
    }

    /**
     * POST /api/v1/categories
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $dto      = CategoryDTO::fromRequest($request->validated(), $tenantId);
        $category = $this->categoryService->create($dto);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/categories/{id}
     */
    public function show(int $id): CategoryResource
    {
        return new CategoryResource($this->categoryService->findOrFail($id));
    }

    /**
     * PUT/PATCH /api/v1/categories/{id}
     */
    public function update(UpdateCategoryRequest $request, int $id): CategoryResource
    {
        $tenantId = $request->attributes->get('tenant_id');
        $category = $this->categoryService->findOrFail($id);
        $dto      = CategoryDTO::fromRequest(
            array_merge($category->toArray(), $request->validated()),
            $tenantId
        );

        return new CategoryResource($this->categoryService->update($category, $dto));
    }

    /**
     * DELETE /api/v1/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryService->findOrFail($id);
        $this->categoryService->delete($category);

        return response()->json([
            'status'  => 'success',
            'message' => 'Category deleted successfully',
        ], Response::HTTP_OK);
    }
}
