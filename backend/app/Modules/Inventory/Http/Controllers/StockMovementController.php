<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Controllers\BaseController;
use App\Modules\Inventory\DTOs\StockMovementDTO;
use App\Modules\Inventory\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stock Movement Controller
 *
 * Handles stock adjustments and transfers
 */
class StockMovementController extends BaseController
{
    public function __construct(
        protected StockMovementService $service
    ) {}

    /**
     * Record a stock adjustment (in or out)
     */
    public function adjustment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'required|in:adjustment_in,adjustment_out',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $dto = new StockMovementDTO(
                productId: $validated['product_id'],
                warehouseId: $validated['warehouse_id'],
                quantity: $validated['quantity'],
                movementType: StockMovementType::from($validated['type']),
                referenceType: 'adjustment',
                referenceId: null,
                referenceNumber: $validated['reference_number'] ?? null,
                notes: $validated['notes'] ?? null,
                unitCost: $validated['unit_cost'] ?? null,
            );

            $movement = $this->service->recordMovement($dto);

            return $this->success(
                $movement,
                'Stock adjustment recorded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Record a stock transfer between warehouses
     */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $movements = $this->service->recordTransfer(
                productId: $validated['product_id'],
                fromWarehouseId: $validated['from_warehouse_id'],
                toWarehouseId: $validated['to_warehouse_id'],
                quantity: $validated['quantity'],
                referenceNumber: $validated['reference_number'] ?? null,
                notes: $validated['notes'] ?? null
            );

            return $this->success(
                $movements,
                'Stock transfer recorded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get stock movement history
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'movement_type' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = $this->service->getRepository()->query()
                ->with(['product', 'warehouse']);

            // Apply filters
            if (! empty($validated['product_id'])) {
                $query->where('product_id', $validated['product_id']);
            }

            if (! empty($validated['warehouse_id'])) {
                $query->where('warehouse_id', $validated['warehouse_id']);
            }

            if (! empty($validated['movement_type'])) {
                $query->where('movement_type', $validated['movement_type']);
            }

            if (! empty($validated['from_date'])) {
                $query->whereDate('created_at', '>=', $validated['from_date']);
            }

            if (! empty($validated['to_date'])) {
                $query->whereDate('created_at', '<=', $validated['to_date']);
            }

            $query->orderBy('created_at', 'desc');

            $perPage = $validated['per_page'] ?? 15;
            $movements = $query->paginate($perPage);

            return $this->success($movements, 'Stock movement history retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get available stock movement types
     */
    public function types(): JsonResponse
    {
        $types = collect(StockMovementType::cases())->map(function ($type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'sign' => $type->getSign(),
                'is_increase' => $type->isIncrease(),
                'is_decrease' => $type->isDecrease(),
            ];
        });

        return $this->success($types, 'Stock movement types retrieved successfully');
    }
}
