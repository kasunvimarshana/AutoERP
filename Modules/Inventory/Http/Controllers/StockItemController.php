<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Http\Resources\StockItemResource;
use Modules\Inventory\Models\StockItem;
use Modules\Inventory\Services\InventoryValuationService;

use Modules\Inventory\Services\StockItemService;

/**
 * Stock Item Controller
 *
 * Handles HTTP requests for viewing stock levels, low stock reports,
 * and inventory valuation reports.
 */
class StockItemController extends Controller
{
    public function __construct(
        private InventoryValuationService $valuationService,
        private StockItemService $stockItemService
    ) {}

    /**
     * Display a listing of stock items.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $tenantId = $request->user()->currentTenant()->id;
        
        $filters = [
            'warehouse_id' => $request->get('warehouse_id'),
            'product_id' => $request->get('product_id'),
            'location_id' => $request->get('location_id'),
            'low_stock' => $request->has('low_stock') && filter_var($request->low_stock, FILTER_VALIDATE_BOOLEAN),
            'search' => $request->get('search'),
        ];

        $perPage = $request->get('per_page', 15);
        $stockItems = $this->stockItemService->getPaginatedStockItems($tenantId, $filters, $perPage);

        return ApiResponse::paginated(
            $stockItems->setCollection(
                $stockItems->getCollection()->map(fn ($item) => new StockItemResource($item))
            ),
            'Stock items retrieved successfully'
        );
    }

    /**
     * Display stock item by product and warehouse.
     */
    public function getByProductAndWarehouse(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
        ]);

        $tenantId = $request->user()->currentTenant()->id;
        
        $stockItem = $this->stockItemService->findByProductAndWarehouse(
            $tenantId,
            $request->product_id,
            $request->warehouse_id
        );

        if (! $stockItem) {
            return ApiResponse::error(
                'Stock item not found',
                404
            );
        }

        $stockItem->load(['product', 'warehouse', 'location']);

        return ApiResponse::success(
            new StockItemResource($stockItem),
            'Stock item retrieved successfully'
        );
    }

    /**
     * Get low stock items report.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $tenantId = $request->user()->currentTenant()->id;
        $perPage = $request->get('per_page', 15);
        $stockItems = $this->stockItemService->getLowStockItems($tenantId, $perPage);

        return ApiResponse::paginated(
            $stockItems->setCollection(
                $stockItems->getCollection()->map(fn ($item) => new StockItemResource($item))
            ),
            'Low stock items retrieved successfully'
        );
    }

    /**
     * Get inventory valuation report.
     */
    public function valuationReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
            'valuation_method' => ['nullable', 'string', 'in:FIFO,LIFO,WEIGHTED_AVERAGE,STANDARD_COST'],
        ]);

        $method = ValuationMethod::tryFrom($request->input('valuation_method', 'WEIGHTED_AVERAGE'))
            ?? ValuationMethod::WEIGHTED_AVERAGE;

        $report = $this->valuationService->calculateWarehouseValue(
            $request->warehouse_id,
            $method
        );

        return ApiResponse::success(
            $report,
            'Inventory valuation report generated successfully'
        );
    }

    /**
     * Get detailed valuation by product.
     */
    public function productValuation(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockItem::class);

        $request->validate([
            'product_id' => ['required', 'string', 'exists:products,id'],
            'warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
            'valuation_method' => ['nullable', 'string', 'in:FIFO,LIFO,WEIGHTED_AVERAGE,STANDARD_COST'],
        ]);

        $method = ValuationMethod::tryFrom($request->input('valuation_method', 'WEIGHTED_AVERAGE'))
            ?? ValuationMethod::WEIGHTED_AVERAGE;

        $valuation = $this->valuationService->calculateStockValue(
            $request->product_id,
            $request->warehouse_id,
            $method
        );

        return ApiResponse::success(
            $valuation,
            'Product valuation calculated successfully'
        );
    }

    /**
     * Display the specified stock item.
     */
    public function show(StockItem $stockItem): JsonResponse
    {
        $this->authorize('view', $stockItem);

        $stockItem->load(['product', 'warehouse', 'location']);

        return ApiResponse::success(
            new StockItemResource($stockItem),
            'Stock item retrieved successfully'
        );
    }
}
