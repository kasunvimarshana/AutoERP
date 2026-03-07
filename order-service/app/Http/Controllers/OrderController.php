<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\OrderServiceInterface;
use App\Exceptions\SagaException;
use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RESTful Order endpoints backed by the Saga orchestrator.
 */
final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

    /**
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $orders   = $this->orderService->listForTenant(
            $tenantId,
            (int) $request->query('per_page', '15')
        );

        return response()->json(['data' => $orders]);
    }

    /**
     * POST /api/v1/orders
     *
     * Initiates a Saga distributed transaction:
     *   Reserve Inventory → Process Payment → Confirm Order → Send Notification
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('user_id');

        try {
            $order = $this->orderService->createOrder(
                $tenantId,
                $userId,
                $request->validated()
            );

            return response()->json([
                'message' => 'Order created and confirmed via Saga transaction.',
                'data'    => $order,
            ], 201);

        } catch (SagaException $e) {
            return response()->json([
                'message' => 'Order creation failed. All changes have been rolled back.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $order    = $this->orderService->find($id, $tenantId);

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json(['data' => $order]);
    }

    /**
     * POST /api/v1/orders/{order}/cancel
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        try {
            $cancelled = $this->orderService->cancelOrder($order);
            return response()->json([
                'message' => 'Order cancelled.',
                'data'    => $cancelled,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
