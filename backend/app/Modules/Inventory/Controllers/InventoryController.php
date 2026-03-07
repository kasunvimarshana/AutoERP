<?php
namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        $filters = $request->only(['product_id', 'product_name', 'warehouse_location', 'low_stock']);
        $inventory = $this->inventoryService->listInventory($filters, $tenant->id);

        return response()->json(['success' => true, 'data' => $inventory]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->inventoryService->getInventory($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_location' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'reserved_quantity' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|array',
        ]);

        $tenant = app('tenant');
        $inventory = $this->inventoryService->createInventory($validated, $tenant->id);

        return response()->json(['success' => true, 'data' => $inventory, 'message' => 'Inventory record created'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_location' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer|min:0',
            'reserved_quantity' => 'sometimes|integer|min:0',
            'reorder_level' => 'sometimes|integer|min:0',
            'unit_cost' => 'sometimes|numeric|min:0',
            'attributes' => 'nullable|array',
        ]);

        $inventory = $this->inventoryService->updateInventory($id, $validated);
        return response()->json(['success' => true, 'data' => $inventory, 'message' => 'Inventory updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->inventoryService->deleteInventory($id);
        return response()->json(['success' => true, 'message' => 'Inventory deleted']);
    }

    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'delta' => 'required|integer',
            'reason' => 'nullable|string|max:500',
        ]);

        $inventory = $this->inventoryService->adjustStock($id, $validated['delta'], $validated['reason'] ?? '');
        return response()->json(['success' => true, 'data' => $inventory, 'message' => 'Stock adjusted']);
    }
}
