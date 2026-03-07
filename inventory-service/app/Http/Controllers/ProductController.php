<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ProductServiceInterface;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RESTful CRUD for the product catalogue.
 *
 * All operations are scoped to the resolved tenant.
 */
final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $products = $this->productService->list($tenantId, (int) $request->query('per_page', '15'));

        return response()->json(['data' => $products]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product  = $this->productService->create($tenantId, $request->validated());

        return response()->json(['data' => $product], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product  = $this->productService->find($id, $tenantId);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json(['data' => $product]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        if ($product->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $updated = $this->productService->update($product, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        if ($product->tenant_id !== $tenantId) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted.']);
    }
}
