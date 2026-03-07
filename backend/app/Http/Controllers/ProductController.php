<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->productService->paginate(
            perPage: (int) $request->get('per_page', 15),
            relations: ['category', 'inventory'],
            filters: $request->only(['status', 'category_id', 'is_trackable']),
            sortBy: $request->get('sort_by', 'name'),
            sortDirection: $request->get('sort_direction', 'asc'),
            search: $request->get('search', '')
        );

        return $this->paginatedResponse(
            $paginator->through(fn ($p) => new ProductResource($p))
        );
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());
        return $this->createdResponse(new ProductResource($product->load('category')));
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id, ['*'], ['category', 'inventory']);

        if (!$product) {
            return $this->notFoundResponse('Product not found');
        }

        return $this->successResponse(new ProductResource($product));
    }

    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());
        return $this->successResponse(new ProductResource($product->load('category')), 'Product updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->productService->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse('Product not found');
        }

        return $this->successResponse(null, 'Product deleted');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $products = $this->productService->getLowStockProducts(
            $request->integer('threshold')
        );

        return $this->successResponse(ProductResource::collection($products));
    }
}
