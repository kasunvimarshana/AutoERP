<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Product\DTOs\CreateProductDTO;
use App\Application\Product\DTOs\UpdateProductDTO;
use App\Application\Product\Services\ProductService;
use App\Domain\Product\Exceptions\ProductAlreadyExistsException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * ProductController
 *
 * Thin controller — delegates all logic to ProductService.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    // GET /api/products
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $filters  = $request->only([
            'category_id', 'status', 'price:gte', 'price:lte',
            'name:like', 'code:like', 'sku:like',
        ]);
        $perPage  = (int) $request->get('per_page', 15);
        $orderBy  = $this->parseOrderBy($request);

        $products = $this->productService->list($tenantId, $filters, $perPage, ['category'], $orderBy);

        return (new ProductCollection($products))->response();
    }

    // GET /api/products/search?q=term
    public function search(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $term     = (string) $request->get('q', '');
        $perPage  = (int) $request->get('per_page', 15);

        $products = $this->productService->search($term, $tenantId, $perPage);

        return (new ProductCollection($products))->response();
    }

    // GET /api/products/{id}
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');
            $product  = $this->productService->findById($id, $tenantId);

            return (new ProductResource($product))->response();

        } catch (ProductNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    // POST /api/products
    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'tenant_id' => $request->attributes->get('tenant_id'),
            ]);

            $product = $this->productService->create(CreateProductDTO::fromArray($data));

            return (new ProductResource($product))->response()->setStatusCode(201);

        } catch (ProductAlreadyExistsException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 409);
        }
    }

    // PUT/PATCH /api/products/{id}
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            $data = array_merge($request->validated(), [
                'tenant_id' => $request->attributes->get('tenant_id'),
            ]);

            $product = $this->productService->update($id, UpdateProductDTO::fromArray($data));

            return (new ProductResource($product))->response();

        } catch (ProductNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    // DELETE /api/products/{id}
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');
            $this->productService->delete($id, $tenantId);

            return response()->json(['message' => 'Product deleted successfully.']);

        } catch (ProductNotFoundException $e) {
            return $this->notFound($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function parseOrderBy(Request $request): array
    {
        $sort      = $request->get('sort',  'created_at');
        $direction = $request->get('order', 'desc');

        $allowed = ['name', 'code', 'price', 'created_at', 'updated_at', 'status'];

        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';
        $direction = in_array(strtolower($direction), ['asc', 'desc'], true)
                   ? strtolower($direction) : 'desc';

        return [$sort => $direction];
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'error' => true], 404);
    }
}
