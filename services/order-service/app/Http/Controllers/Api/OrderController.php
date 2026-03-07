<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Return a paginated, filtered, and sorted list of orders.
     *
     * GET /api/v1/orders
     */
    public function index(Request $request): OrderCollection|JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'customer_id',
                'date_from',
                'date_to',
                'sort_by',
                'sort_direction',
                'per_page',
            ]);

            $orders = $this->orderService->getAllOrders($filters);

            return new OrderCollection($orders);
        } catch (Throwable $e) {
            Log::error('Failed to fetch orders', [
                'error'   => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve orders.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a single order by ID.
     *
     * GET /api/v1/orders/{id}
     */
    public function show(int $id): OrderResource|JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if ($order === null) {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            return new OrderResource($order);
        } catch (Throwable $e) {
            Log::error('Failed to fetch order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to retrieve order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new order (runs the Saga).
     *
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request): OrderResource|JsonResponse
    {
        try {
            $data = $request->validated();

            // Inject the authenticated customer's Keycloak sub as customer_id
            if (empty($data['customer_id'])) {
                $data['customer_id'] = $request->attributes->get('user_id');
            }

            $order = $this->orderService->createOrder($data);

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Failed to create order', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to create order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update non-status order metadata.
     *
     * PUT /api/v1/orders/{id}
     * PATCH /api/v1/orders/{id}
     */
    public function update(UpdateOrderRequest $request, int $id): OrderResource|JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder($id, $request->validated());

            return new OrderResource($order);
        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            Log::error('Failed to update order', [
                'id'    => $id,
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to update order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft-delete a cancelled order (admin only).
     *
     * DELETE /api/v1/orders/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->orderService->deleteOrder($id);

            if (! $deleted) {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            return response()->json(['message' => 'Order deleted successfully.'], 200);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('Failed to delete order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm a pending order.
     *
     * POST /api/v1/orders/{id}/confirm
     */
    public function confirm(int $id): OrderResource|JsonResponse
    {
        try {
            $order = $this->orderService->confirmOrder($id);

            return new OrderResource($order);
        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            Log::error('Failed to confirm order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to confirm order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an order (runs the compensating Saga).
     *
     * POST /api/v1/orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): OrderResource|JsonResponse
    {
        try {
            $performedBy = $request->attributes->get('user_id', 'customer');

            $order = $this->orderService->cancelOrder($id, $performedBy);

            return new OrderResource($order);
        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            Log::error('Failed to cancel order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to cancel order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark an order as shipped.
     *
     * POST /api/v1/orders/{id}/ship
     */
    public function ship(int $id): OrderResource|JsonResponse
    {
        try {
            $order = $this->orderService->shipOrder($id);

            return new OrderResource($order);
        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            Log::error('Failed to ship order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to ship order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark an order as delivered.
     *
     * POST /api/v1/orders/{id}/deliver
     */
    public function deliver(int $id): OrderResource|JsonResponse
    {
        try {
            $order = $this->orderService->deliverOrder($id);

            return new OrderResource($order);
        } catch (RuntimeException $e) {
            $status = str_contains($e->getMessage(), 'not found') ? 404 : 422;

            return response()->json(['message' => $e->getMessage()], $status);
        } catch (Throwable $e) {
            Log::error('Failed to deliver order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to deliver order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return orders belonging to the currently authenticated customer.
     *
     * GET /api/v1/orders/my-orders
     */
    public function myOrders(Request $request): OrderCollection|JsonResponse
    {
        try {
            $customerId = $request->attributes->get('user_id');

            if (empty($customerId)) {
                return response()->json(['message' => 'Unauthorized: customer identity missing.'], 401);
            }

            $filters = $request->only([
                'status',
                'date_from',
                'date_to',
                'sort_by',
                'sort_direction',
                'per_page',
            ]);

            $orders = $this->orderService->getOrdersByCustomer($customerId, $filters);

            return new OrderCollection($orders);
        } catch (Throwable $e) {
            Log::error('Failed to fetch customer orders', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve your orders.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
