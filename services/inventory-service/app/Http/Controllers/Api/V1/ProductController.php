<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\ProductServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductServiceInterface $productService)
    {
    }

    public function index(Request $request): ProductCollection
    {
        $tenantId = $request->attributes->get('tenant_id');
        $products = $this->productService->list($tenantId, $request->all());
        return new ProductCollection($products);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product  = $this->productService->create($tenantId, $request->validated());
        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): ProductResource
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product  = $this->productService->findById($tenantId, $id);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, string $id): ProductResource
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product  = $this->productService->update($tenantId, $id, $request->validated());
        return new ProductResource($product);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $this->productService->delete($tenantId, $id);
        return response()->json(['message' => 'Product deleted successfully.'], Response::HTTP_OK);
    }

    public function search(Request $request): ProductCollection
    {
        $tenantId = $request->attributes->get('tenant_id');
        $query    = $request->input('q', '');
        $filters  = $request->except('q');
        $products = $this->productService->search($tenantId, $query, $filters);
        return new ProductCollection($products);
    }

    public function getLowStock(Request $request): ProductCollection
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $threshold = $request->input('threshold');
        $products  = $this->productService->getLowStockProducts($tenantId, $threshold);
        return new ProductCollection($products);
    }
}
