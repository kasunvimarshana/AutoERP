<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\InventoryServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inventory management endpoints:
 *   - Stock levels with pagination and filtering
 *   - Low-stock alerts (items at or below reorder point)
 *   - Expiry alerts (batches expiring within N days)
 *   - FIFO cost lookup per SKU / warehouse
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryServiceInterface $inventoryService
    ) {}

    /**
     * GET /api/v1/inventory/stock
     * List stock items with optional warehouse / product filters.
     */
    public function stock(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['warehouse_id', 'product_id']);

        return response()->json(
            $this->inventoryService->paginateStock($tenantId, $filters, $perPage)
        );
    }

    /**
     * GET /api/v1/inventory/alerts/low-stock
     * Return stock items whose available quantity is at or below their reorder point.
     */
    public function lowStock(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $warehouseId = $request->query('warehouse_id');

        $items = $this->inventoryService->getLowStockItems($tenantId, $warehouseId ?: null);

        return response()->json(['data' => $items]);
    }

    /**
     * GET /api/v1/inventory/alerts/expiring
     * Return stock batches expiring within the given number of days (default 30).
     */
    public function expiring(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $daysAhead = max(1, (int) $request->query('days', 30));
        $warehouseId = $request->query('warehouse_id');

        $batches = $this->inventoryService->getExpiringBatches($tenantId, $daysAhead, $warehouseId ?: null);

        return response()->json(['data' => $batches]);
    }

    /**
     * GET /api/v1/inventory/fifo-cost
     * Return the FIFO weighted-average cost for a product/warehouse combination.
     */
    public function fifoCost(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $request->validate([
            'warehouse_id' => ['required', 'uuid'],
            'product_id' => ['required', 'uuid'],
            'variant_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        $tenantId = $request->user()->tenant_id;

        $cost = $this->inventoryService->getFifoCost(
            $tenantId,
            $request->input('warehouse_id'),
            $request->input('product_id'),
            $request->input('variant_id')
        );

        return response()->json(['cost_per_unit' => $cost]);
    }
}
