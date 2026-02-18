<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Http\Requests\StoreSalesOrderRequest;
use Modules\Sales\Http\Requests\UpdateSalesOrderRequest;
use Modules\Sales\Http\Resources\SalesOrderResource;
use Modules\Sales\Services\SalesOrderService;

/**
 * Sales Order API Controller
 *
 * Handles API requests for sales order management.
 */
class SalesOrderController extends Controller
{
    /**
     * SalesOrderController constructor.
     */
    public function __construct(
        protected SalesOrderService $salesOrderService
    ) {}

    /**
     * Display a listing of sales orders.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $salesOrders = $this->salesOrderService->getPaginated($perPage);

        return SalesOrderResource::collection($salesOrders)
            ->response();
    }

    /**
     * Store a newly created sales order.
     */
    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        if (! empty($lines)) {
            $salesOrder = $this->salesOrderService->createWithLines($data, $lines);
        } else {
            $salesOrder = $this->salesOrderService->create($data);
        }

        return (new SalesOrderResource($salesOrder->load('lines')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified sales order.
     */
    public function show(int $id): JsonResponse
    {
        $salesOrder = $this->salesOrderService->findById($id, true);

        if (! $salesOrder) {
            return response()->json([
                'message' => 'Sales order not found',
            ], 404);
        }

        return (new SalesOrderResource($salesOrder))->response();
    }

    /**
     * Update the specified sales order.
     */
    public function update(UpdateSalesOrderRequest $request, int $id): JsonResponse
    {
        $salesOrder = $this->salesOrderService->update($id, $request->validated());

        return (new SalesOrderResource($salesOrder->load('lines')))->response();
    }

    /**
     * Remove the specified sales order.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->salesOrderService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Sales order not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Sales order deleted successfully',
        ], 200);
    }

    /**
     * Search sales orders.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $salesOrders = $this->salesOrderService->search($query);

        return SalesOrderResource::collection($salesOrders)
            ->response();
    }

    /**
     * Confirm a sales order.
     */
    public function confirm(int $id): JsonResponse
    {
        try {
            $salesOrder = $this->salesOrderService->confirm($id);

            return response()->json([
                'message' => 'Sales order confirmed successfully',
                'sales_order' => new SalesOrderResource($salesOrder->load('lines')),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Calculate order totals.
     */
    public function calculateTotals(int $id): JsonResponse
    {
        try {
            $salesOrder = $this->salesOrderService->calculateTotals($id);

            return response()->json([
                'message' => 'Totals calculated successfully',
                'sales_order' => new SalesOrderResource($salesOrder->load('lines')),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
