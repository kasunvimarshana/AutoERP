<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * ProductController
 *
 * RESTful endpoints for Service A (Product Service).
 * All responses include the product record *plus* the related inventory
 * records fetched from Service B (Inventory Service) via HTTP.
 *
 * Route map:
 *   GET    /api/products            → index   (list + inventory)
 *   POST   /api/products            → store   (create + event)
 *   GET    /api/products/{id}       → show    (single + inventory)
 *   PUT    /api/products/{id}       → update  (update + event)
 *   DELETE /api/products/{id}       → destroy (soft-delete + event)
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * @queryParam name        string Filter by product name (partial match)
     * @queryParam category    string Filter by category
     * @queryParam is_active   bool   Filter by active flag
     */
    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only(['name', 'category', 'is_active']);
        $products = $this->service->list($filters);

        return $this->ok($products, 'Products retrieved successfully');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->service->create($request->validated());

            return $this->ok($product, 'Product created successfully', 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to create product', $e);
        }
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->service->findOrFail($id);

            return $this->ok($product, 'Product retrieved successfully');
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->service->update($id, $request->validated());

            return $this->ok($product, 'Product updated successfully');
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        } catch (\Throwable $e) {
            return $this->error('Failed to update product', $e);
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->ok(null, 'Product deleted successfully. Related inventory will be cleaned up asynchronously.');
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete product', $e);
        }
    }

    // ── Response helpers ──────────────────────────────────────────────────────

    private function ok(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    private function error(string $message, \Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], 500);
    }
}
