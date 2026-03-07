<?php
namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        $filters = $request->only(['search', 'category', 'is_active', 'name']);
        $products = $this->productService->listProducts($filters, $tenant->id);

        return response()->json(['success' => true, 'data' => $products]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->productService->getProduct($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|max:100|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'attributes' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $tenant = app('tenant');
        $product = $this->productService->createProduct($validated, $tenant->id);

        return response()->json(['success' => true, 'data' => $product, 'message' => 'Product created'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sku' => "sometimes|string|max:100|unique:products,sku,{$id}",
            'price' => 'sometimes|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'attributes' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $product = $this->productService->updateProduct($id, $validated);

        return response()->json(['success' => true, 'data' => $product, 'message' => 'Product updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);
        return response()->json(['success' => true, 'message' => 'Product deleted']);
    }
}
