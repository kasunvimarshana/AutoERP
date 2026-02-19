<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Events\StockIssued;
use Modules\Inventory\Events\StockReceived;
use Modules\Inventory\Events\StockTransferred;
use Modules\Inventory\Http\Requests\StoreStockMovementRequest;
use Modules\Inventory\Http\Resources\StockMovementResource;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Repositories\StockMovementRepository;
use Modules\Inventory\Services\StockMovementService;

/**
 * Stock Movement Controller
 *
 * Handles HTTP requests for stock movements including receive, issue,
 * transfer, and adjustment operations.
 */
class StockMovementController extends Controller
{
    public function __construct(
        private StockMovementService $stockMovementService,
        private StockMovementRepository $stockMovementRepository
    ) {}

    /**
     * Display a listing of stock movements.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockMovement::class);

        $filters = [
            'type' => $request->get('type'),
            'product_id' => $request->get('product_id'),
            'warehouse_id' => $request->get('warehouse_id'),
            'from_warehouse_id' => $request->get('from_warehouse_id'),
            'to_warehouse_id' => $request->get('to_warehouse_id'),
            'reference_type' => $request->get('reference_type'),
            'reference_id' => $request->get('reference_id'),
            'from_date' => $request->get('date_from'),
            'to_date' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        // Filter out null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $perPage = $request->get('per_page', 15);
        $movements = $this->stockMovementRepository->searchMovements($filters, $perPage);

        return ApiResponse::paginated(
            $movements->setCollection(
                $movements->getCollection()->map(fn ($movement) => new StockMovementResource($movement))
            ),
            'Stock movements retrieved successfully'
        );
    }

    /**
     * Display the specified stock movement.
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        $this->authorize('view', $stockMovement);

        $stockMovement->load([
            'product',
            'fromWarehouse',
            'toWarehouse',
            'fromLocation',
            'toLocation',
            'batchLot',
            'serialNumber',
        ]);

        return ApiResponse::success(
            new StockMovementResource($stockMovement),
            'Stock movement retrieved successfully'
        );
    }

    /**
     * Process stock receipt.
     */
    public function receive(StoreStockMovementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $movement = DB::transaction(function () use ($data) {
            $movement = $this->stockMovementService->processReceipt($data);
            event(new StockReceived($movement));

            return $movement;
        });

        $movement->load(['product', 'toWarehouse', 'toLocation']);

        return ApiResponse::created(
            new StockMovementResource($movement),
            'Stock received successfully'
        );
    }

    /**
     * Process stock issue.
     */
    public function issue(StoreStockMovementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $movement = DB::transaction(function () use ($data) {
            $movement = $this->stockMovementService->processIssue($data);
            event(new StockIssued($movement));

            return $movement;
        });

        $movement->load(['product', 'fromWarehouse', 'fromLocation']);

        return ApiResponse::created(
            new StockMovementResource($movement),
            'Stock issued successfully'
        );
    }

    /**
     * Process stock transfer between warehouses.
     */
    public function transfer(StoreStockMovementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $movement = DB::transaction(function () use ($data) {
            $movement = $this->stockMovementService->processTransfer($data);
            event(new StockTransferred($movement));

            return $movement;
        });

        $movement->load(['product', 'fromWarehouse', 'toWarehouse', 'fromLocation', 'toLocation']);

        return ApiResponse::created(
            new StockMovementResource($movement),
            'Stock transferred successfully'
        );
    }

    /**
     * Process stock adjustment.
     */
    public function adjust(StoreStockMovementRequest $request): JsonResponse
    {
        $this->authorize('approve', StockMovement::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $movement = DB::transaction(function () use ($data) {
            $movement = $this->stockMovementService->processAdjustment($data);
            event(new StockAdjusted($movement));

            return $movement;
        });

        $movement->load(['product', 'toWarehouse', 'toLocation']);

        return ApiResponse::created(
            new StockMovementResource($movement),
            'Stock adjusted successfully'
        );
    }
}
