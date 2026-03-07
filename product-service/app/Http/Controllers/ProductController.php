<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ProductController
 *
 * Handles HTTP requests for Product CRUD operations.
 * Each response is enriched with related inventory data fetched
 * from the Inventory Service (Node.js) via HTTP, providing a
 * complete cross-service view.
 *
 * Endpoints:
 *   GET    /api/v1/products          List products with inventory
 *   POST   /api/v1/products          Create product (fires event → inventory)
 *   GET    /api/v1/products/{id}     Show product with inventory
 *   PUT    /api/v1/products/{id}     Update product (fires event → inventory sync)
 *   DELETE /api/v1/products/{id}     Delete product (fires event → inventory cascade)
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * GET /api/v1/products
     *
     * List all products (paginated) enriched with inventory data.
     * Supports query filters: category, is_active, search, per_page.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['category', 'is_active', 'search']);
            $perPage = (int) $request->get('per_page', 15);

            $products = $this->productService->getAllProducts($filters, $perPage);

            // Enrich each product with inventory data from Inventory Service
            $items = collect($products->items())->map(function ($product) {
                return array_merge(
                    $product->toArray(),
                    ['inventory' => $this->fetchInventoryForProduct($product->name)]
                );
            });

            return response()->json([
                'success' => true,
                'data'    => $items,
                'meta'    => [
                    'current_page' => $products->currentPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'last_page'    => $products->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ProductController@index error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/products
     *
     * Create a new product. Fires ProductCreated event after DB commit,
     * which publishes to RabbitMQ for the Inventory Service to consume.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data'    => array_merge(
                    $product->toArray(),
                    ['inventory' => []]
                ),
            ], 201);
        } catch (\Exception $e) {
            Log::error('ProductController@store error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/products/{id}
     *
     * Retrieve a single product enriched with its inventory data
     * from the Inventory Service, providing a complete cross-service view.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product   = $this->productService->getProductById($id);
            $inventory = $this->fetchInventoryForProduct($product->name);

            return response()->json([
                'success' => true,
                'data'    => array_merge($product->toArray(), ['inventory' => $inventory]),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('ProductController@show error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/products/{id}
     *
     * Update a product. Fires ProductUpdated event after DB commit.
     * The Inventory Service consumes this to sync product_name in inventory records.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product   = $this->productService->updateProduct($id, $request->validated());
            $inventory = $this->fetchInventoryForProduct($product->name);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data'    => array_merge($product->toArray(), ['inventory' => $inventory]),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('ProductController@update error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/products/{id}
     *
     * Soft-delete a product. Fires ProductDeleted event after DB commit.
     * The Inventory Service consumes this to delete related inventory records
     * (cross-service cascade delete via event-driven messaging).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct($id);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully. Related inventory records will be removed by the Inventory Service.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('ProductController@destroy error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch inventory records for a product from the Inventory Service.
     *
     * Uses HTTP to call GET /api/v1/inventory?product_name={name}.
     * Falls back gracefully to an empty array if the Inventory Service is unavailable.
     *
     * @param  string $productName
     * @return array
     */
    private function fetchInventoryForProduct(string $productName): array
    {
        try {
            $inventoryServiceUrl = rtrim(config('services.inventory.url'), '/');

            $response = Http::timeout(5)
                ->get("{$inventoryServiceUrl}/api/v1/inventory", [
                    'product_name' => $productName,
                ]);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            Log::warning('ProductController: Inventory Service returned non-200', [
                'product_name' => $productName,
                'status'       => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::warning('ProductController: Could not reach Inventory Service', [
                'product_name' => $productName,
                'error'        => $e->getMessage(),
            ]);

            return [];
        }
    }
}
