<?php
namespace Modules\Inventory\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Infrastructure\Models\ProductCategoryModel;
use Modules\Shared\Application\ResponseFormatter;
class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategoryModel::latest()->paginate(50);
        return ResponseFormatter::paginated($categories);
    }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|uuid',
            'description' => 'nullable|string',
        ]);
        $category = ProductCategoryModel::create($data);
        return ResponseFormatter::success($category, 'Category created.', 201);
    }
    public function show(string $id): JsonResponse
    {
        $category = ProductCategoryModel::with('children')->find($id);
        if (!$category) return ResponseFormatter::error('Not found.', [], 404);
        return ResponseFormatter::success($category);
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $category = ProductCategoryModel::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|uuid',
            'description' => 'nullable|string',
        ]);
        $category->update($data);
        return ResponseFormatter::success($category->fresh(), 'Updated.');
    }
    public function destroy(string $id): JsonResponse
    {
        ProductCategoryModel::findOrFail($id)->delete();
        return ResponseFormatter::success(null, 'Deleted.');
    }
}
