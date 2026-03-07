<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderItemCollection;
use App\Http\Resources\OrderItemResource;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderItemController extends Controller
{
    public function __construct(
        private readonly OrderRepositoryInterface     $orderRepository,
        private readonly OrderItemRepositoryInterface $orderItemRepository,
    ) {}

    /**
     * Return all items for a given order.
     *
     * GET /api/v1/orders/{orderId}/items
     */
    public function index(int $orderId): OrderItemCollection|JsonResponse
    {
        try {
            $order = $this->orderRepository->findById($orderId);

            if ($order === null) {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            $items = $this->orderItemRepository->getByOrderId($orderId);

            return new OrderItemCollection($items);
        } catch (Throwable $e) {
            Log::error('Failed to fetch order items', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve order items.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a single order item.
     *
     * GET /api/v1/orders/{orderId}/items/{itemId}
     */
    public function show(int $orderId, int $itemId): OrderItemResource|JsonResponse
    {
        try {
            $order = $this->orderRepository->findById($orderId);

            if ($order === null) {
                return response()->json(['message' => 'Order not found.'], 404);
            }

            $item = $this->orderItemRepository->findById($itemId);

            if ($item === null || $item->order_id !== $orderId) {
                return response()->json(['message' => 'Order item not found.'], 404);
            }

            return new OrderItemResource($item);
        } catch (Throwable $e) {
            Log::error('Failed to fetch order item', [
                'order_id' => $orderId,
                'item_id'  => $itemId,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve order item.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
