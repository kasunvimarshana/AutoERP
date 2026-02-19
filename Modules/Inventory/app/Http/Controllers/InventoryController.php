<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Requests\AdjustStockRequest;
use Modules\Inventory\Requests\StoreInventoryItemRequest;
use Modules\Inventory\Requests\TransferStockRequest;
use Modules\Inventory\Requests\UpdateInventoryItemRequest;
use Modules\Inventory\Resources\InventoryItemResource;
use Modules\Inventory\Services\InventoryItemService;

/**
 * Inventory Controller
 *
 * Handles HTTP requests for Inventory Item operations
 */
class InventoryController extends Controller
{
    /**
     * InventoryController constructor
     */
    public function __construct(
        private readonly InventoryItemService $inventoryService
    ) {}

    /**
     * Display a listing of inventory items
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category', 'branch_id', 'low_stock', 'paginate', 'per_page']);

        $items = $this->inventoryService->getAll($filters);

        return response()->json([
            'success' => true,
            'message' => 'Inventory items retrieved successfully',
            'data' => InventoryItemResource::collection($items),
        ]);
    }

    /**
     * Store a newly created inventory item
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = $this->inventoryService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'data' => new InventoryItemResource($item),
        ], 201);
    }

    /**
     * Display the specified inventory item
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->inventoryService->getById($id);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item retrieved successfully',
            'data' => new InventoryItemResource($item),
        ]);
    }

    /**
     * Update the specified inventory item
     */
    public function update(UpdateInventoryItemRequest $request, int $id): JsonResponse
    {
        $item = $this->inventoryService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'data' => new InventoryItemResource($item),
        ]);
    }

    /**
     * Remove the specified inventory item
     */
    public function destroy(int $id): JsonResponse
    {
        $this->inventoryService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully',
        ]);
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(AdjustStockRequest $request, int $id): JsonResponse
    {
        $item = $this->inventoryService->adjustStock($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted successfully',
            'data' => new InventoryItemResource($item),
        ]);
    }

    /**
     * Transfer stock between branches
     */
    public function transferStock(TransferStockRequest $request): JsonResponse
    {
        $result = $this->inventoryService->transferStock($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stock transferred successfully',
            'data' => [
                'from_item' => new InventoryItemResource($result['from_item']),
                'to_item' => new InventoryItemResource($result['to_item']),
            ],
        ]);
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');
        $items = $this->inventoryService->getLowStockItems($branchId ? (int) $branchId : null);

        return response()->json([
            'success' => true,
            'message' => 'Low stock items retrieved successfully',
            'data' => InventoryItemResource::collection($items),
        ]);
    }

    /**
     * Get reorder suggestions
     */
    public function reorderSuggestions(Request $request): JsonResponse
    {
        $branchId = $request->query('branch_id');
        $suggestions = $this->inventoryService->getReorderSuggestions($branchId ? (int) $branchId : null);

        return response()->json([
            'success' => true,
            'message' => 'Reorder suggestions retrieved successfully',
            'data' => $suggestions,
        ]);
    }
}
