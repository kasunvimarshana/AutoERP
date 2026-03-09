<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\StockServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ReserveStockRequest;
use Illuminate\Http\JsonResponse;

/**
 * Stock Controller - Saga participant endpoints for inventory stock operations.
 */
class StockController extends Controller
{
    public function __construct(
        private readonly StockServiceInterface $stockService
    ) {}

    /**
     * Reserve stock (called by Saga Orchestrator).
     */
    public function reserve(ReserveStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $success = $this->stockService->reserveStock(
            $data['product_id'],
            $data['warehouse_id'],
            $data['quantity'],
            $data['saga_id']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Stock reserved successfully.' : 'Insufficient stock.',
            'saga_id' => $data['saga_id'],
        ], $success ? 200 : 422);
    }

    /**
     * Release stock reservation (Saga compensation).
     */
    public function release(ReserveStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $success = $this->stockService->releaseReservation(
            $data['product_id'],
            $data['warehouse_id'],
            $data['quantity'],
            $data['saga_id']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Reservation released.' : 'Failed to release reservation.',
            'saga_id' => $data['saga_id'],
        ]);
    }

    /**
     * Deduct stock (Saga final commit).
     */
    public function deduct(ReserveStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $success = $this->stockService->deductStock(
            $data['product_id'],
            $data['warehouse_id'],
            $data['quantity'],
            $data['saga_id']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Stock deducted.' : 'Failed to deduct stock.',
            'saga_id' => $data['saga_id'],
        ]);
    }

    /**
     * Restore stock (Saga compensation for deduct).
     */
    public function restore(ReserveStockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $success = $this->stockService->restoreStock(
            $data['product_id'],
            $data['warehouse_id'],
            $data['quantity'],
            $data['saga_id']
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Stock restored.' : 'Failed to restore stock.',
            'saga_id' => $data['saga_id'],
        ]);
    }

    /**
     * Check stock availability.
     */
    public function availability(string $productId, string $warehouseId, int $quantity): JsonResponse
    {
        $available = $this->stockService->checkAvailability($productId, $warehouseId, $quantity);

        return response()->json([
            'success' => true,
            'available' => $available,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'requested_quantity' => $quantity,
        ]);
    }
}
