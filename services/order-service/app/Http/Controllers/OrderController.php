<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends BaseController
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * GET /api/orders
     * List orders with filtering, sorting, and optional pagination.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->orderService->getOrders($request);

            return $this->paginatedResponse($result, 'Orders retrieved successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@index failed', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve orders', null, 500);
        }
    }

    /**
     * POST /api/orders
     * Create a new order via Saga orchestration.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return $this->createdResponse($order, 'Order created successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@store failed', ['error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * GET /api/orders/{id}
     * Retrieve a single order enriched with product data.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderWithDetails($id);

            if (! $order) {
                return $this->notFoundResponse('Order not found');
            }

            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@show failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve order', null, 500);
        }
    }

    /**
     * PUT/PATCH /api/orders/{id}
     * Update order fields (excluding status – use dedicated endpoint).
     */
    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder($id, $request->validated());

            if (! $order) {
                return $this->notFoundResponse('Order not found');
            }

            return $this->successResponse($order, 'Order updated successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * DELETE /api/orders/{id}
     * Soft-delete an order (must be in cancellable state).
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->orderService->cancelOrder($id);

            return $this->successResponse(null, 'Order cancelled successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@destroy failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * PATCH /api/orders/{id}/status
     * Transition order to a new status.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
        ]);

        try {
            $order = $this->orderService->updateOrderStatus($id, $request->input('status'));

            return $this->successResponse($order, 'Order status updated successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@updateStatus failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * GET /api/orders/status/{status}
     * Retrieve all orders for a given status.
     */
    public function byStatus(string $status): JsonResponse
    {
        try {
            $orders = $this->orderService->getOrdersByStatus($status);

            return $this->successResponse($orders, "Orders with status '{$status}' retrieved");
        } catch (Throwable $e) {
            Log::error('OrderController@byStatus failed', ['status' => $status, 'error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve orders by status', null, 500);
        }
    }

    /**
     * GET /api/orders/customer/{customerId}
     * Retrieve all orders for a specific customer.
     */
    public function byCustomer(Request $request, string $customerId): JsonResponse
    {
        try {
            $orders = $this->orderService->getOrdersByCustomer($customerId, $request);

            return $this->paginatedResponse($orders, 'Customer orders retrieved successfully');
        } catch (Throwable $e) {
            Log::error('OrderController@byCustomer failed', ['customer_id' => $customerId, 'error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve customer orders', null, 500);
        }
    }
}
