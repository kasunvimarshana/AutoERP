<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list(
            $request->only(['search', 'category_id', 'is_active', 'min_price', 'max_price', 'tag', 'sort_by', 'sort_dir']),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Product::class);

        $data = array_merge($request->validated(), [
            'tenant_id' => app('current_tenant_id'),
            'created_by' => $request->user()?->id,
        ]);
        $product = $this->productService->create($data);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->findById($id);
        $this->authorize('update', $product);

        $updated = $this->productService->update($id, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);
        $this->authorize('delete', $product);

        $this->productService->delete($id);

        return response()->json(['message' => 'Product deleted successfully.'], 204);
    }

    public function inventory(int $id): JsonResponse
    {
        $inventoryData = $this->productService->getInventory($id);

        return response()->json(['data' => $inventoryData]);
    }
}
