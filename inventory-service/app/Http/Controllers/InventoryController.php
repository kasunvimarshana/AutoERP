<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\InventoryRepositoryInterface;
use App\Http\Requests\ReserveStockRequest;
use App\Http\Requests\ReleaseStockRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages inventory-level operations: listing, reserving, and releasing stock.
 *
 * The reserve and release endpoints are called internally by the
 * Order Service Saga orchestrator during distributed transactions.
 */
final class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventoryRepository,
    ) {}

    /**
     * GET /api/v1/inventory
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $items = $this->inventoryRepository->listForTenant(
            $tenantId,
            (int) $request->query('per_page', '15')
        );

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/v1/inventory/reserve
     *
     * Saga Step: Reserve stock for an order.
     * Called by the Order Service Saga orchestrator.
     */
    public function reserve(ReserveStockRequest $request): JsonResponse
    {
        $data      = $request->validated();
        $tenantId  = $request->attributes->get('tenant_id');
        $reserved  = $this->inventoryRepository->reserveStock(
            $data['product_id'],
            $tenantId,
            $data['quantity']
        );

        if (!$reserved) {
            return response()->json([
                'message' => 'Insufficient stock.',
                'success' => false,
            ], 422);
        }

        return response()->json([
            'message' => 'Stock reserved successfully.',
            'success' => true,
        ]);
    }

    /**
     * POST /api/v1/inventory/release
     *
     * Saga Compensation: Release reserved stock on order failure.
     */
    public function release(ReleaseStockRequest $request): JsonResponse
    {
        $data     = $request->validated();
        $tenantId = $request->attributes->get('tenant_id');
        $released = $this->inventoryRepository->releaseStock(
            $data['product_id'],
            $tenantId,
            $data['quantity']
        );

        if (!$released) {
            return response()->json([
                'message' => 'Failed to release stock.',
                'success' => false,
            ], 422);
        }

        return response()->json([
            'message' => 'Stock released successfully.',
            'success' => true,
        ]);
    }
}
