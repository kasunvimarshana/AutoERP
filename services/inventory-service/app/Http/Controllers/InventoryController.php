<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Inventory\Services\InventoryService;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * InventoryController
 *
 * Thin controller — delegates all logic to InventoryService.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    // GET /api/inventory
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $filters  = $request->only(['product_id', 'warehouse_id', 'quantity_available:lte', 'quantity_available:gte']);
        $perPage  = (int) $request->get('per_page', 15);

        $items = $this->inventoryService->list($tenantId, $filters, $perPage);

        return response()->json($items);
    }

    // GET /api/inventory/product/{productId}
    public function showByProduct(Request $request, string $productId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $item     = $this->inventoryService->getByProduct($productId, $tenantId);

        if ($item === null) {
            return response()->json(['message' => 'Inventory item not found.', 'error' => true], 404);
        }

        return response()->json(['data' => $item]);
    }

    // POST /api/inventory/reserve
    public function reserve(Request $request): JsonResponse
    {
        $request->validate([
            'order_id'  => ['required', 'string', 'uuid'],
            'tenant_id' => ['required', 'string', 'uuid'],
            'items'     => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'uuid'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        try {
            $reservation = $this->inventoryService->reserve(
                $request->input('order_id'),
                $request->input('tenant_id'),
                $request->input('items'),
                $request->input('saga_id')
            );

            return response()->json([
                'data' => [
                    'reservation_id' => $reservation->id,
                    'status'         => $reservation->status,
                    'expires_at'     => $reservation->expires_at?->toIso8601String(),
                ],
            ], 201);

        } catch (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 422);
        }
    }

    // DELETE /api/inventory/reserve/{reservationId}
    public function releaseReservation(string $reservationId): JsonResponse
    {
        try {
            $this->inventoryService->releaseReservation($reservationId);

            return response()->json(['message' => 'Reservation released successfully.']);

        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true], 422);
        }
    }

    // POST /api/inventory/adjust
    public function adjustStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'string', 'uuid'],
            'delta'      => ['required', 'integer'],
            'reason'     => ['sometimes', 'string', 'max:255'],
        ]);

        $tenantId = $request->attributes->get('tenant_id');

        $item = $this->inventoryService->adjustStock(
            $request->input('product_id'),
            $tenantId,
            $request->integer('delta'),
            $request->input('reason', 'manual_adjustment')
        );

        return response()->json(['data' => $item]);
    }
}
