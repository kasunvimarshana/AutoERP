<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Api\ApiController;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends ApiController
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get stock balance for a product
     */
    public function balance(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        try {
            $result = $this->stockService->getStockBalance(
                $request->product_id,
                $request->product_variant_id,
                $request->branch_id
            );

            return $this->successResponse($result, 'Stock balance retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Add stock (receive goods)
     */
    public function addStock(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->addStock($request->all());

            if ($result['success']) {
                return $this->createdResponse($result, $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Remove stock (issue goods)
     */
    public function removeStock(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->removeStock($request->all());

            if ($result['success']) {
                return $this->successResponse($result, $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Transfer stock between branches
     */
    public function transferStock(Request $request): JsonResponse
    {
        $request->validate([
            'from_branch_id' => 'required|integer|exists:branches,id',
            'to_branch_id' => 'required|integer|exists:branches,id|different:from_branch_id',
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->transferStock($request->all());

            if ($result['success']) {
                return $this->successResponse($result, $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Adjust stock (reconciliation)
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'target_balance' => 'required|numeric|min:0',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->adjustStock($request->all());

            if ($result['success']) {
                return $this->successResponse($result, $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get available batches for picking (FIFO/FEFO)
     */
    public function availableBatches(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'required_quantity' => 'nullable|numeric|min:0',
            'strategy' => 'nullable|string|in:FIFO,FEFO',
        ]);

        try {
            $result = $this->stockService->getAvailableBatches(
                $request->product_id,
                $request->product_variant_id,
                $request->branch_id,
                $request->required_quantity ?? 0,
                $request->strategy ?? 'FEFO'
            );

            return $this->successResponse($result, 'Available batches retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Pick stock using FIFO or FEFO strategy
     */
    public function pickStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'branch_id' => 'required|integer|exists:branches,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'strategy' => 'nullable|string|in:FIFO,FEFO',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->stockService->pickStock(
                $request->product_id,
                $request->quantity,
                $request->branch_id,
                $request->product_variant_id,
                $request->strategy ?? 'FEFO',
                [
                    'reference_type' => $request->reference_type,
                    'reference_id' => $request->reference_id,
                    'notes' => $request->notes,
                ]
            );

            if ($result['success']) {
                return $this->successResponse($result, $result['message']);
            }

            return $this->errorResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get stock movement history
     */
    public function movementHistory(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $result = $this->stockService->getMovementHistory(
                $request->product_id,
                $request->product_variant_id,
                $request->branch_id,
                $request->start_date,
                $request->end_date
            );

            return $this->paginatedResponse($result['data'], 'Movement history retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
