<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\OrderServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderSagaRequest;
use App\Http\Requests\Order\CancelOrderSagaRequest;
use App\Http\Resources\Order\OrderCollection;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Order Controller - Thin HTTP layer, Saga participant.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService
    ) {}

    /**
     * List orders for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getOrders(
            $request->input('_tenant_id', ''),
            $request->query()
        );

        return response()->json([
            'success' => true,
            'data' => new OrderCollection($orders),
        ]);
    }

    /**
     * Get a specific order.
     */
    public function show(string $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);
        return (new OrderResource($order))->response();
    }

    /**
     * Create an order as a Saga participant (called by Saga Orchestrator).
     */
    public function createSaga(CreateOrderSagaRequest $request): JsonResponse
    {
        $order = $this->orderService->createForSaga($request->validated());

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
            'order_id' => $order->id,
        ], 201);
    }

    /**
     * Cancel an order - Saga compensation endpoint.
     */
    public function cancelSaga(CancelOrderSagaRequest $request, string $orderId): JsonResponse
    {
        $data = $request->validated();
        $success = $this->orderService->cancelForSaga($orderId, $data['saga_id']);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Order cancelled.' : 'Order could not be cancelled.',
            'order_id' => $orderId,
        ]);
    }

    /**
     * Confirm an order.
     */
    public function confirm(string $id): JsonResponse
    {
        $order = $this->orderService->confirm($id);
        return (new OrderResource($order))->response();
    }
}
