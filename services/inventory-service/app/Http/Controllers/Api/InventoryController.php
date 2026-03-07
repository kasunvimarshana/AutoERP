<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Http\Resources\InventoryCollection;
use App\Http\Resources\InventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * Return a paginated, filtered, and sorted list of inventory items.
     */
    public function index(Request $request): InventoryCollection|JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'warehouse_location',
                'product_id',
                'low_stock',
                'out_of_stock',
                'sort_by',
                'sort_direction',
                'per_page',
            ]);

            $inventory = $this->inventoryService->getAllInventory($filters);

            return new InventoryCollection($inventory);
        } catch (Throwable $e) {
            Log::error('Failed to fetch inventory', [
                'error'   => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve inventory.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a single inventory item by ID.
     */
    public function show(int $id): InventoryResource|JsonResponse
    {
        try {
            $inventory = $this->inventoryService->getInventoryById($id);

            if ($inventory === null) {
                return response()->json(['message' => 'Inventory record not found.'], 404);
            }

            return new InventoryResource($inventory);
        } catch (Throwable $e) {
            Log::error('Failed to fetch inventory', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to retrieve inventory record.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new inventory record.
     */
    public function store(StoreInventoryRequest $request): InventoryResource|JsonResponse
    {
        try {
            $inventory = $this->inventoryService->createInventory($request->validated());

            return (new InventoryResource($inventory))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            Log::error('Failed to create inventory', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to create inventory record.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update inventory metadata (warehouse location, reorder levels, status, etc.).
     * Use adjustInventory to change stock quantities.
     */
    public function update(UpdateInventoryRequest $request, int $id): InventoryResource|JsonResponse
    {
        try {
            $inventory = $this->inventoryService->updateInventory($id, $request->validated());

            if ($inventory === null) {
                return response()->json(['message' => 'Inventory record not found.'], 404);
            }

            return new InventoryResource($inventory);
        } catch (Throwable $e) {
            Log::error('Failed to update inventory', [
                'id'    => $id,
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to update inventory record.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft-delete an inventory record.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->inventoryService->deleteInventory($id);

            if (! $deleted) {
                return response()->json(['message' => 'Inventory record not found.'], 404);
            }

            return response()->json(['message' => 'Inventory record deleted successfully.'], 200);
        } catch (Throwable $e) {
            Log::error('Failed to delete inventory', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete inventory record.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adjust stock quantity (receipt, adjustment, sale, etc.).
     *
     * POST /api/v1/inventory/{id}/adjust
     */
    public function adjustInventory(AdjustInventoryRequest $request, int $id): InventoryResource|JsonResponse
    {
        try {
            $data      = $request->validated();
            $inventory = $this->inventoryService->adjustStock(
                inventoryId:   $id,
                type:          $data['type'],
                quantity:      (int) $data['quantity'],
                notes:         $data['notes'] ?? '',
                referenceType: $data['reference_type'] ?? null,
                referenceId:   $data['reference_id'] ?? null,
                performedBy:   $data['performed_by'] ?? ($request->attributes->get('user_id') ?? 'api'),
            );

            return new InventoryResource($inventory);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            Log::error('Failed to adjust inventory', [
                'id'    => $id,
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to adjust inventory.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return all inventory records for a given product.
     *
     * GET /api/v1/inventory/product/{productId}
     */
    public function getInventoryByProductId(int $productId): InventoryCollection|JsonResponse
    {
        try {
            $inventories = $this->inventoryService->getInventoryByProductId($productId);

            // Wrap the collection in a paginator-compatible resource
            return response()->json([
                'data' => InventoryResource::collection($inventories),
                'meta' => [
                    'service'    => 'inventory-service',
                    'version'    => '1.0.0',
                    'product_id' => $productId,
                    'total'      => $inventories->count(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch inventory by product', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve inventory for product.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
