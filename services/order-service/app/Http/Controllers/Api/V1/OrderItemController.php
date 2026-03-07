<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderItemResource;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class OrderItemController extends Controller
{
    public function __construct(
        private readonly OrderItemRepositoryInterface $orderItemRepository,
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function index(Request $request, string $orderId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $tenantId = $request->get('tenant_id');
            $this->orderRepository->findById($orderId, $tenantId);
            $items = $this->orderItemRepository->findByOrderId($orderId);

            return OrderItemResource::collection($items);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_not_found'], 404);
        } catch (\Throwable $e) {
            Log::error('Failed to list order items', ['orderId' => $orderId, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to list order items', 'error' => 'server_error'], 500);
        }
    }

    public function show(string $orderId, string $id): OrderItemResource|JsonResponse
    {
        try {
            $item = $this->orderItemRepository->findById($id);
            if (! $item || $item->order_id !== $orderId) {
                return response()->json(['message' => 'Order item not found', 'error' => 'not_found'], 404);
            }

            return new OrderItemResource($item);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch order item', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to fetch order item', 'error' => 'server_error'], 500);
        }
    }

    public function store(Request $request, string $orderId): JsonResponse
    {
        $request->validate([
            'product_id'   => 'required|uuid',
            'product_name' => 'nullable|string',
            'quantity'     => 'required|integer|min:1',
            'unit_price'   => 'required|numeric|min:0',
            'metadata'     => 'nullable|array',
        ]);

        try {
            $tenantId = $request->get('tenant_id');
            $this->orderRepository->findById($orderId, $tenantId);

            $data             = $request->validated();
            $data['order_id'] = $orderId;
            $data['subtotal'] = $data['quantity'] * $data['unit_price'];

            $item = $this->orderItemRepository->create($data);

            return response()->json(new OrderItemResource($item), 201);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'order_error'], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to add order item', ['orderId' => $orderId, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to add order item', 'error' => 'server_error'], 500);
        }
    }

    public function update(Request $request, string $orderId, string $id): OrderItemResource|JsonResponse
    {
        $request->validate([
            'quantity'   => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
            'metadata'   => 'nullable|array',
        ]);

        try {
            $item = $this->orderItemRepository->findById($id);
            if (! $item || $item->order_id !== $orderId) {
                return response()->json(['message' => 'Order item not found', 'error' => 'not_found'], 404);
            }

            $item = $this->orderItemRepository->update($id, $request->validated());

            return new OrderItemResource($item);
        } catch (\Throwable $e) {
            Log::error('Failed to update order item', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update order item', 'error' => 'server_error'], 500);
        }
    }

    public function destroy(string $orderId, string $id): JsonResponse
    {
        try {
            $item = $this->orderItemRepository->findById($id);
            if (! $item || $item->order_id !== $orderId) {
                return response()->json(['message' => 'Order item not found', 'error' => 'not_found'], 404);
            }

            $this->orderItemRepository->delete($id);

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            Log::error('Failed to delete order item', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to delete order item', 'error' => 'server_error'], 500);
        }
    }
}
