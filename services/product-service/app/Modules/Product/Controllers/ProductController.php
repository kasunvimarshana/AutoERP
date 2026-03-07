<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\Requests\StoreProductRequest;
use App\Modules\Product\Requests\UpdateProductRequest;
use App\Modules\Product\Resources\ProductCollection;
use App\Modules\Product\Resources\ProductResource;
use App\Modules\Product\Services\Interfaces\ProductServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ProductServiceInterface $productService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category', 'status', 'min_price', 'max_price', 'sort_by', 'sort_dir']);
        $perPage = min((int) $request->get('per_page', 15), 100);

        $products = $this->productService->listProducts($filters, $perPage);

        return (new ProductCollection($products))->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $dto = ProductDTO::fromArray(array_merge(
            $request->validated(),
            ['created_by' => $request->attributes->get('user_id')]
        ));

        $product = $this->productService->createProduct($dto);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);
        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());
        return (new ProductResource($product))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);
        return response()->json(['message' => 'Product deleted successfully.'], 200);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->productService->getCategories();
        return response()->json(['data' => $categories]);
    }
}
