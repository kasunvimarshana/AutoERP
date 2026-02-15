<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\BaseController;
use App\Http\Resources\StockLedgerResource;
use App\Modules\Inventory\Services\InventoryService;
use App\Http\Requests\Inventory\StockInRequest;
use App\Http\Requests\Inventory\StockOutRequest;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Requests\Inventory\StockTransferRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends BaseController
{
    /**
     * @var InventoryService
     */
    protected $inventoryService;

    /**
     * InventoryController constructor.
     *
     * @param InventoryService $inventoryService
     */
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Record stock IN transaction.
     *
     * @param StockInRequest $request
     * @return JsonResponse
     */
    public function stockIn(StockInRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $ledgerEntry = $this->inventoryService->stockIn($validated);

            return $this->successResponse(
                new StockLedgerResource($ledgerEntry),
                'Stock IN recorded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record stock IN: ' . $e->getMessage());
        }
    }

    /**
     * Record stock OUT transaction.
     *
     * @param StockOutRequest $request
     * @return JsonResponse
     */
    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $ledgerEntry = $this->inventoryService->stockOut($validated);

            return $this->successResponse(
                new StockLedgerResource($ledgerEntry),
                'Stock OUT recorded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record stock OUT: ' . $e->getMessage());
        }
    }

    /**
     * Record stock adjustment.
     *
     * @param StockAdjustmentRequest $request
     * @return JsonResponse
     */
    public function stockAdjustment(StockAdjustmentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $ledgerEntry = $this->inventoryService->stockAdjustment($validated);

            return $this->successResponse(
                new StockLedgerResource($ledgerEntry),
                'Stock adjustment recorded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to record stock adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Transfer stock between locations.
     *
     * @param StockTransferRequest $request
     * @return JsonResponse
     */
    public function stockTransfer(StockTransferRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->inventoryService->stockTransfer($validated);

            return $this->successResponse($result, 'Stock transfer completed successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to transfer stock: ' . $e->getMessage());
        }
    }

    /**
     * Get current stock balance.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentBalance(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'variant_id' => 'nullable|exists:product_variants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'location_id' => 'nullable|exists:locations,id',
            ]);

            $balance = $this->inventoryService->getCurrentBalance(
                $validated['product_id'],
                $validated['branch_id'] ?? null,
                $validated['location_id'] ?? null,
                $validated['variant_id'] ?? null
            );

            return $this->successResponse([
                'product_id' => $validated['product_id'],
                'current_balance' => $balance,
            ], 'Current balance retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get current balance: ' . $e->getMessage());
        }
    }

    /**
     * Get stock movements history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMovements(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            $movements = $this->inventoryService->getStockMovements(
                $validated['product_id'],
                new \DateTime($validated['start_date']),
                new \DateTime($validated['end_date']),
                $validated['branch_id'] ?? null
            );

            return $this->successResponse(
                StockLedgerResource::collection($movements),
                'Stock movements retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get stock movements: ' . $e->getMessage());
        }
    }

    /**
     * Get expiring items.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getExpiringItems(Request $request): JsonResponse
    {
        try {
            $daysThreshold = $request->input('days_threshold', 30);
            $branchId = $request->input('branch_id');

            $expiringItems = $this->inventoryService->getExpiringItems($daysThreshold, $branchId);

            return $this->successResponse($expiringItems, 'Expiring items retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get expiring items: ' . $e->getMessage());
        }
    }

    /**
     * Calculate stock valuation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateValuation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'method' => 'required|in:fifo,lifo,average',
                'branch_id' => 'nullable|exists:branches,id',
            ]);

            $valuation = $this->inventoryService->calculateStockValuation(
                $validated['product_id'],
                $validated['method'],
                $validated['branch_id'] ?? null
            );

            return $this->successResponse($valuation, 'Stock valuation calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate stock valuation: ' . $e->getMessage());
        }
    }
}
