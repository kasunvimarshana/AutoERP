<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Inventory\Http\Requests\InventoryAdjustmentRequest;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function adjust(InventoryAdjustmentRequest $request): JsonResponse
    {
        try {
            $this->inventoryService->inbound(
                $request->product_id,
                $request->quantity,
                $request->warehouse_id,
                $request->location_id,
                $request->unit_cost,
                'adjustment',
                null, // reference_id optional
                $request->batch_id,
                $request->serial_numbers
            );

            return response()->json(['message' => 'Adjustment processed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}