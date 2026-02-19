<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Inventory\Events\StockCountCancelled;
use Modules\Inventory\Events\StockCountCompleted;
use Modules\Inventory\Events\StockCountReconciled;
use Modules\Inventory\Events\StockCountStarted;
use Modules\Inventory\Http\Requests\StoreStockCountRequest;
use Modules\Inventory\Http\Requests\UpdateStockCountItemRequest;
use Modules\Inventory\Http\Requests\UpdateStockCountRequest;
use Modules\Inventory\Http\Resources\StockCountResource;
use Modules\Inventory\Models\StockCount;
use Modules\Inventory\Repositories\StockCountRepository;
use Modules\Inventory\Services\StockCountService;

/**
 * Stock Count Controller
 *
 * Handles HTTP requests for stock count lifecycle including creation,
 * starting, completing, reconciling, and cancelling counts.
 */
class StockCountController extends Controller
{
    public function __construct(
        private StockCountService $stockCountService,
        private StockCountRepository $stockCountRepository
    ) {}

    /**
     * Display a listing of stock counts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StockCount::class);

        $filters = [
            'status' => $request->get('status'),
            'warehouse_id' => $request->get('warehouse_id'),
            'from_date' => $request->get('date_from'),
            'to_date' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];

        // Filter out null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $perPage = $request->get('per_page', 15);
        $stockCounts = $this->stockCountRepository->searchStockCounts($filters, $perPage);

        return ApiResponse::paginated(
            $stockCounts->setCollection(
                $stockCounts->getCollection()->map(fn ($count) => new StockCountResource($count))
            ),
            'Stock counts retrieved successfully'
        );
    }
    }

    /**
     * Store a newly created stock count.
     */
    public function store(StoreStockCountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['created_by'] = $request->user()->id;

        $items = $data['items'] ?? [];
        unset($data['items']);

        $stockCount = DB::transaction(function () use ($data, $items) {
            return $this->stockCountService->create($data, $items);
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::created(
            new StockCountResource($stockCount),
            'Stock count created successfully'
        );
    }

    /**
     * Display the specified stock count.
     */
    public function show(StockCount $stockCount): JsonResponse
    {
        $this->authorize('view', $stockCount);

        $stockCount->load(['warehouse', 'items.product', 'items.location']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count retrieved successfully'
        );
    }

    /**
     * Update the specified stock count.
     */
    public function update(UpdateStockCountRequest $request, StockCount $stockCount): JsonResponse
    {
        if (! $stockCount->status->canModify()) {
            return ApiResponse::error(
                'Stock count cannot be modified in its current status',
                422
            );
        }

        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);

        $stockCount = DB::transaction(function () use ($stockCount, $data, $items) {
            return $this->stockCountService->update($stockCount->id, $data, $items);
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count updated successfully'
        );
    }

    /**
     * Remove the specified stock count.
     */
    public function destroy(StockCount $stockCount): JsonResponse
    {
        $this->authorize('delete', $stockCount);

        if (! $stockCount->status->canModify()) {
            return ApiResponse::error(
                'Stock count cannot be deleted in its current status',
                422
            );
        }

        DB::transaction(function () use ($stockCount) {
            $this->stockCountService->delete($stockCount->id);
        });

        return ApiResponse::success(
            null,
            'Stock count deleted successfully'
        );
    }

    /**
     * Start the stock count process.
     */
    public function start(StockCount $stockCount): JsonResponse
    {
        $this->authorize('start', $stockCount);

        if (! $stockCount->status->canStart()) {
            return ApiResponse::error(
                'Stock count cannot be started in its current status',
                422
            );
        }

        $stockCount = DB::transaction(function () use ($stockCount) {
            $stockCount = $this->stockCountService->start($stockCount->id);
            event(new StockCountStarted($stockCount));

            return $stockCount;
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count started successfully'
        );
    }

    /**
     * Update count quantities for items.
     */
    public function updateItems(UpdateStockCountItemRequest $request, StockCount $stockCount): JsonResponse
    {
        $this->authorize('update', $stockCount);

        if (! $stockCount->status->isInProgress()) {
            return ApiResponse::error(
                'Can only update items for in-progress stock counts',
                422
            );
        }

        $items = $request->validated()['items'];

        $stockCount = DB::transaction(function () use ($stockCount, $items) {
            return $this->stockCountService->updateItems($stockCount->id, $items);
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count items updated successfully'
        );
    }

    /**
     * Complete the stock count.
     */
    public function complete(StockCount $stockCount): JsonResponse
    {
        $this->authorize('complete', $stockCount);

        if (! $stockCount->status->isInProgress()) {
            return ApiResponse::error(
                'Only in-progress stock counts can be completed',
                422
            );
        }

        $stockCount = DB::transaction(function () use ($stockCount) {
            $stockCount = $this->stockCountService->complete($stockCount->id);
            event(new StockCountCompleted($stockCount));

            return $stockCount;
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count completed successfully'
        );
    }

    /**
     * Reconcile the stock count and create adjustments.
     */
    public function reconcile(Request $request, StockCount $stockCount): JsonResponse
    {
        $this->authorize('reconcile', $stockCount);

        if (! $stockCount->status->isCompleted()) {
            return ApiResponse::error(
                'Only completed stock counts can be reconciled',
                422
            );
        }

        $request->validate([
            'auto_adjust' => ['boolean'],
        ]);

        $autoAdjust = $request->input('auto_adjust', true);

        $stockCount = DB::transaction(function () use ($stockCount, $autoAdjust) {
            $stockCount = $this->stockCountService->reconcile($stockCount->id, $autoAdjust);
            event(new StockCountReconciled($stockCount));

            return $stockCount;
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count reconciled successfully'
        );
    }

    /**
     * Cancel the stock count.
     */
    public function cancel(Request $request, StockCount $stockCount): JsonResponse
    {
        $this->authorize('update', $stockCount);

        if (! $stockCount->status->canCancel()) {
            return ApiResponse::error(
                'Stock count cannot be cancelled in its current status',
                422
            );
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $stockCount = DB::transaction(function () use ($stockCount, $request) {
            $stockCount = $this->stockCountService->cancel($stockCount->id, $request->input('reason'));
            event(new StockCountCancelled($stockCount));

            return $stockCount;
        });

        $stockCount->load(['warehouse', 'items.product']);

        return ApiResponse::success(
            new StockCountResource($stockCount),
            'Stock count cancelled successfully'
        );
    }
}
