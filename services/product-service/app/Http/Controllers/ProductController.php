<?php

namespace App\Http\Controllers;

use App\DTOs\ProductDTO;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    public function __construct(private readonly ProductService $productService) {}

    // -------------------------------------------------------------------------
    // GET /api/products
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $products = $this->productService->getProducts($request, $tenantId);

        return $this->paginatedResponse($products, 'Products retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/products
    // -------------------------------------------------------------------------

    public function store(CreateProductRequest $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $dto = new ProductDTO(
            name:          $request->validated('name'),
            sku:           $request->validated('sku'),
            price:         (float) $request->validated('price'),
            tenantId:      $tenantId,
            categoryId:    $request->validated('category_id'),
            description:   $request->validated('description'),
            costPrice:     (float) $request->validated('cost_price', 0),
            unit:          $request->validated('unit'),
            weight:        $request->validated('weight') !== null ? (float) $request->validated('weight') : null,
            dimensions:    $request->validated('dimensions', []),
            images:        $request->validated('images', []),
            attributes:    $request->validated('attributes', []),
            isActive:      (bool) $request->validated('is_active', true),
            minStockLevel: $request->validated('min_stock_level') !== null ? (int) $request->validated('min_stock_level') : null,
            maxStockLevel: $request->validated('max_stock_level') !== null ? (int) $request->validated('max_stock_level') : null,
            reorderPoint:  $request->validated('reorder_point')  !== null ? (int) $request->validated('reorder_point')  : null,
        );

        try {
            $product = $this->productService->createProduct($dto);

            return $this->createdResponse($product, 'Product created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to create product', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/products/{id}
    // -------------------------------------------------------------------------

    public function show(Request $request, int|string $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (! $product) {
            return $this->notFoundResponse('Product not found');
        }

        if (! $this->tenantMatches($request, $product->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        return $this->successResponse($product, 'Product retrieved');
    }

    // -------------------------------------------------------------------------
    // PUT/PATCH /api/products/{id}
    // -------------------------------------------------------------------------

    public function update(UpdateProductRequest $request, int|string $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (! $product) {
            return $this->notFoundResponse('Product not found');
        }

        if (! $this->tenantMatches($request, $product->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        try {
            $updated = $this->productService->updateProduct($id, $request->validated());

            return $this->successResponse($updated, 'Product updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update product', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // DELETE /api/products/{id}
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (! $product) {
            return $this->notFoundResponse('Product not found');
        }

        if (! $this->tenantMatches($request, $product->tenant_id)) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        try {
            $this->productService->deleteProduct($id);

            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to delete product', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/products/low-stock
    // -------------------------------------------------------------------------

    public function lowStock(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $products = $this->productService->getLowStockProducts();

        // Filter by tenant if present
        if ($tenantId) {
            $products = $products->filter(fn ($p) => (string) $p->tenant_id === (string) $tenantId)->values();
        }

        return $this->successResponse($products, 'Low stock products retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/products/batch
    // Retrieve multiple products by IDs (for cross-service calls from Inventory)
    // -------------------------------------------------------------------------

    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $products = $this->productService->getProductsByIds($request->input('ids'));

        return $this->successResponse($products, 'Products retrieved');
    }

    // -------------------------------------------------------------------------
    // Tenant helpers
    // -------------------------------------------------------------------------

    private function resolveTenantId(Request $request): ?int
    {
        $tenantId = $request->header('X-Tenant-ID') ?? $request->user()?->tenant_id;

        return $tenantId ? (int) $tenantId : null;
    }

    private function tenantMatches(Request $request, mixed $resourceTenantId): bool
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return true;
        }

        return (string) $resourceTenantId === (string) $tenantId;
    }
}
