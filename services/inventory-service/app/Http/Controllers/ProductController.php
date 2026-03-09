<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\InventoryServiceInterface;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product Controller
 *
 * Handles HTTP requests for product inventory operations.
 * Thin controller - all business logic delegated to InventoryService.
 */
class ProductController extends Controller
{
    public function __construct(
        protected readonly InventoryServiceInterface $inventoryService
    ) {}

    /**
     * List all products for the tenant with optional filters/pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $filters = $request->only([
            'search', 'category_id', 'is_active',
            'sort_by', 'sort_dir', 'per_page', 'page',
        ]);

        $result = $this->inventoryService->listProducts($tenantId, $filters);

        if ($result['paginated']) {
            return response()->json([
                'success' => true,
                'data'    => $result['data'],
                'meta'    => $result['meta'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data'],
        ]);
    }

    /**
     * Create a new product.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $data = array_merge(
            $request->validated(),
            ['tenant_id' => $request->attributes->get('tenant_id')]
        );

        $product = $this->inventoryService->createProduct($data);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data'    => $product,
        ], 201);
    }

    /**
     * Get a specific product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->inventoryService->getProduct($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->inventoryService->updateProduct($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data'    => $product,
        ]);
    }

    /**
     * Delete a product (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->inventoryService->deleteProduct($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Reserve stock for an order (used by Order Service via Saga).
     */
    public function reserveStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'order_id' => ['required', 'string'],
        ]);

        $reservationId = $this->inventoryService->reserveStock(
            $id,
            $request->integer('quantity'),
            $request->string('order_id')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock reserved successfully.',
            'data'    => ['reservation_id' => $reservationId],
        ]);
    }

    /**
     * Release a stock reservation (Saga compensation).
     */
    public function releaseReservation(Request $request, string $reservationId): JsonResponse
    {
        $released = $this->inventoryService->releaseReservation($reservationId);

        if (!$released) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation not found or already released.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock reservation released.',
        ]);
    }

    /**
     * Get products below reorder level.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $products = app(\App\Domain\Contracts\ProductRepositoryInterface::class)
            ->findLowStock($tenantId);

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }
}
