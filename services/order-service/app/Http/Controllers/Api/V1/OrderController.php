<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PlaceOrderDTO;
use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): OrderCollection
    {
        $tenantId = $request->get('tenant_id');
        $filters  = array_filter([
            'status' => $request->query('status'),
        ]);

        $orders = $this->orderService->getOrdersForTenant($tenantId, $filters);

        return new OrderCollection($orders);
    }

    public function show(string $id): OrderResource|JsonResponse
    {
        try {
            $tenantId = request()->get('tenant_id');
            $order    = $this->orderService->getOrder($id, $tenantId);

            return new OrderResource($order);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_not_found'], 404);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to fetch order', 'error' => 'server_error'], 500);
        }
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $dto   = PlaceOrderDTO::fromRequest($request);
            $order = $this->orderService->placeOrder($dto);

            return response()->json(new OrderResource($order), 201);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_error'], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to place order', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['message' => 'Failed to place order', 'error' => 'server_error'], 500);
        }
    }

    public function update(UpdateOrderRequest $request, string $id): OrderResource|JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            $order    = $this->orderService->updateOrder($id, $tenantId, $request->validated());

            return new OrderResource($order);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_error'], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to update order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update order', 'error' => 'server_error'], 500);
        }
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $id): OrderResource|JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            $order    = $this->orderService->updateStatus($id, $request->input('status'), $tenantId);

            return new OrderResource($order);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_error'], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to update order status', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update order status', 'error' => 'server_error'], 500);
        }
    }

    public function cancel(CancelOrderRequest $request, string $id): OrderResource|JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            $order    = $this->orderService->cancelOrder($id, $tenantId);

            return new OrderResource($order);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_error'], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to cancel order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to cancel order', 'error' => 'server_error'], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $tenantId = request()->get('tenant_id');
            $this->orderService->deleteOrder($id, $tenantId);

            return response()->json(null, 204);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_not_found'], 404);
        } catch (\Throwable $e) {
            Log::error('Failed to delete order', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to delete order', 'error' => 'server_error'], 500);
        }
    }
}
