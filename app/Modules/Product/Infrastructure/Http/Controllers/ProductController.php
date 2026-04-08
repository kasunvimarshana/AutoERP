<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Application\Contracts\ProductServiceInterface;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $products = $this->productService->getAllProducts($tenantId);
        return response()->json(ProductResource::collection($products));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $product = $this->productService->createProduct($tenantId, $request->all());
        return response()->json(new ProductResource($product), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $product = $this->productService->getProduct($tenantId, $id);
        return response()->json(new ProductResource($product));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $product = $this->productService->updateProduct($tenantId, $id, $request->all());
        return response()->json(new ProductResource($product));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->productService->deleteProduct($tenantId, $id);
        return response()->json(null, 204);
    }
}
