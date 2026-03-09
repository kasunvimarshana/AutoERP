<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\InventoryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product Controller - thin HTTP layer only.
 * Business logic resides in InventoryService.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly InventoryServiceInterface $inventoryService
    ) {}

    /**
     * List products with optional pagination, filtering, search.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->input('_tenant_id');
        $products = $this->inventoryService->getProducts($tenantId, $request->query());

        return response()->json([
            'success' => true,
            'data' => new ProductCollection($products),
        ]);
    }

    /**
     * Create a new product.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->input('_tenant_id');

        $product = $this->inventoryService->createProduct($data);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /**
     * Get a specific product.
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->inventoryService->getProduct($id);

        return (new ProductResource($product))->response();
    }

    /**
     * Update a product.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->inventoryService->updateProduct($id, $request->validated());

        return (new ProductResource($product))->response();
    }

    /**
     * Delete a product.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->inventoryService->deleteProduct($id);

        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }
}
