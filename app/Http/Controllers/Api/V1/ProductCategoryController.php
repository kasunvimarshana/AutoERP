<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductCategoryService $productCategoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['parent_id', 'search']);

        return response()->json($this->productCategoryService->paginate($tenantId, $filters, $perPage));
    }

    public function tree(Request $request): JsonResponse
    {
        return response()->json(
            $this->productCategoryService->tree($request->user()->tenant_id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:150'],
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_categories,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json($this->productCategoryService->create($data), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:150'],
            'parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_categories,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        return response()->json($this->productCategoryService->update($id, $data));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('products.manage'), 403);
        $this->productCategoryService->delete($id);

        return response()->json(null, 204);
    }
}
