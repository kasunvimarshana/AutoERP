<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Sales\SalesOrderResource;
use App\Modules\Sales\Services\SalesOrderService;
use App\Http\Requests\Sales\StoreSalesOrderRequest;
use App\Http\Requests\Sales\UpdateSalesOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesOrderController extends BaseController
{
    protected SalesOrderService $salesOrderService;

    public function __construct(SalesOrderService $salesOrderService)
    {
        $this->salesOrderService = $salesOrderService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $orders = $this->salesOrderService->getAll([], $perPage);

            return $this->paginatedResponse(
                SalesOrderResource::collection($orders),
                'Sales orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve orders: ' . $e->getMessage());
        }
    }

    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->salesOrderService->createOrderWithItems($request->validated());

            return $this->successResponse(
                new SalesOrderResource($order),
                'Sales order created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->findOrFail($id);
            return $this->successResponse(new SalesOrderResource($order));
        } catch (\Exception $e) {
            return $this->errorResponse('Order not found', 404);
        }
    }

    public function update(UpdateSalesOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->updateOrderWithItems($id, $request->validated());
            return $this->successResponse(new SalesOrderResource($order), 'Order updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update order: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->salesOrderService->delete($id);
            return $this->successResponse(null, 'Order deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete order: ' . $e->getMessage());
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->approveOrder($id);
            return $this->successResponse(new SalesOrderResource($order), 'Order approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve order: ' . $e->getMessage());
        }
    }

    public function reserveInventory(int $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->reserveInventory($id);
            return $this->successResponse(new SalesOrderResource($order), 'Inventory reserved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reserve inventory: ' . $e->getMessage());
        }
    }

    public function createFromQuote(int $quoteId): JsonResponse
    {
        try {
            $order = $this->salesOrderService->createFromQuote($quoteId);
            return $this->successResponse(new SalesOrderResource($order), 'Order created from quote successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order from quote: ' . $e->getMessage());
        }
    }
}
