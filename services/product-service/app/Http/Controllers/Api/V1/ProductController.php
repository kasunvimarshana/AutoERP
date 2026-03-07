<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Services\InventoryClientService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly InventoryClientService $inventoryClient,
    ) {}

    /**
     * GET /api/v1/products
     * Returns a paginated, filterable, sortable product list.
     *
     * Supported query params (via spatie/laravel-query-builder):
     *   filter[name], filter[sku], filter[category_id], filter[status], filter[is_active]
     *   sort=name,-price,created_at
     *   include=category
     *   page[number], page[size]
     */
    public function index(Request $request): ProductCollection
    {
        $perPage  = min((int) $request->query('per_page', 15), 100);
        $paginator = $this->productService->list($perPage);

        return new ProductCollection($paginator);
    }

    /**
     * POST /api/v1/products
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('jwt_sub', 'system');

        $dto     = ProductDTO::fromRequest($request->validated(), $tenantId);
        $product = $this->productService->create($dto, $userId);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/products/{id}
     * Includes inventory data from inventory-service with graceful fallback.
     */
    public function show(Request $request, int $id): ProductResource
    {
        $tenantId = $request->attributes->get('tenant_id');
        $product  = $this->productService->findOrFail($id);

        // Fetch inventory data; null returned on connectivity failure
        $inventoryData = $this->inventoryClient->getInventoryForProduct($product->id, $tenantId);

        return (new ProductResource($product))->withInventoryData($inventoryData);
    }

    /**
     * PUT/PATCH /api/v1/products/{id}
     */
    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('jwt_sub', 'system');

        $product = $this->productService->findOrFail($id);
        $dto     = ProductDTO::fromRequest(
            array_merge($product->toArray(), $request->validated()),
            $tenantId
        );

        $updated = $this->productService->update($product, $dto, $userId);

        return new ProductResource($updated);
    }

    /**
     * DELETE /api/v1/products/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId  = $request->attributes->get('jwt_sub', 'system');
        $product = $this->productService->findOrFail($id);

        $this->productService->delete($product, $userId);

        return response()->json([
            'status'  => 'success',
            'message' => 'Product deleted successfully',
        ], Response::HTTP_OK);
    }
}
