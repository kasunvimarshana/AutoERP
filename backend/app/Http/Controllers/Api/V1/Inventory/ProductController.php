<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Application\DTOs\ProductDTO;
use App\Application\Services\Inventory\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Thin controller for Product / Inventory endpoints.
 *
 * All business logic lives in ProductService.
 */
final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * GET /api/v1/products
     *
     * Supports: ?search=, ?category=, ?sort_by=, ?sort_dir=, ?per_page=, ?page=
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Product::class);

        $result = $this->productService->list($request->query());

        return ProductResource::collection($result);
    }

    /**
     * POST /api/v1/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Product::class);

        $tenantId = app('tenant.manager')->getCurrentTenantId();
        $product  = $this->productService->create(ProductDTO::fromRequest($request), $tenantId);

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/products/{id}
     */
    public function show(int $id): ProductResource
    {
        $product = $this->productService->get($id);
        $this->authorize('view', $product);

        return ProductResource::make($product);
    }

    /**
     * PUT /api/v1/products/{id}
     */
    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $product = $this->productService->get($id);
        $this->authorize('update', $product);

        $updated = $this->productService->update($id, $request->validated());

        return ProductResource::make($updated);
    }

    /**
     * DELETE /api/v1/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->get($id);
        $this->authorize('delete', $product);

        $this->productService->delete($id);

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    /**
     * POST /api/v1/products/{id}/stock
     *
     * Body: { "delta": <signed integer>, "reason": "<string>" }
     */
    public function adjustStock(Request $request, int $id): ProductResource
    {
        $product = $this->productService->get($id);
        $this->authorize('adjustStock', $product);

        $request->validate([
            'delta'  => ['required', 'integer', 'not_in:0'],
            'reason' => ['sometimes', 'string', 'max:255'],
        ]);

        $updated = $this->productService->adjustStock(
            $id,
            (int) $request->integer('delta'),
            $request->string('reason')->toString() ?: 'manual'
        );

        return ProductResource::make($updated);
    }

    /**
     * GET /api/v1/products/low-stock
     */
    public function lowStock(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', \App\Models\Product::class);

        return ProductResource::collection($this->productService->getLowStock());
    }
}
