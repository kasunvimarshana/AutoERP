<?php

namespace App\Modules\InventoryManagement\Http\Controllers;

use App\Core\Base\BaseController;
use OpenApi\Attributes as OA;
use App\Modules\InventoryManagement\Services\InventoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends BaseController
{
    protected InventoryItemService $inventoryItemService;

    public function __construct(InventoryItemService $inventoryItemService)
    {
        $this->inventoryItemService = $inventoryItemService;
    }

    /**
     * Display a listing of inventory items
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'category' => $request->input('category'),
                'status' => $request->input('status'),
                'low_stock' => $request->input('low_stock'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $inventoryItems = $this->inventoryItemService->search($criteria);

            return $this->success($inventoryItems);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $inventoryItem = $this->inventoryItemService->create($data);

            return $this->created($inventoryItem, 'Inventory item created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified inventory item
     */
    public function show(int $id): JsonResponse
    {
        try {
            $inventoryItem = $this->inventoryItemService->findByIdOrFail($id);
            $inventoryItem->load('stockMovements');

            return $this->success($inventoryItem);
        } catch (\Exception $e) {
            return $this->notFound('Inventory item not found');
        }
    }

    /**
     * Update the specified inventory item
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $inventoryItem = $this->inventoryItemService->update($id, $request->all());

            return $this->success($inventoryItem, 'Inventory item updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified inventory item
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->inventoryItemService->delete($id);

            return $this->success(null, 'Inventory item deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Adjust stock for inventory item
     */
    public function adjustStock(Request $request, int $id): JsonResponse
    {
        try {
            $inventoryItem = $this->inventoryItemService->adjustStock(
                $id,
                $request->input('quantity'),
                $request->input('type'),
                $request->input('reason')
            );

            return $this->success($inventoryItem, 'Stock adjusted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
