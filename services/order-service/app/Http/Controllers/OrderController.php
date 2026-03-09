<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\OrderServiceInterface;
use App\Http\Requests\CreateOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Order Controller
 *
 * Thin controller for order operations.
 * Delegates all logic to OrderService.
 */
class OrderController extends Controller
{
    public function __construct(
        protected readonly OrderServiceInterface $orderService
    ) {}

    /**
     * List orders for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $filters = $request->only(['status', 'user_id', 'search', 'sort_by', 'sort_dir', 'per_page', 'page']);

        $result = $this->orderService->listOrders($tenantId, $filters);

        if ($result['paginated']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => $result['meta'],
            ]);
        }

        return response()->json(['success' => true, 'data' => $result['data']]);
    }

    /**
     * Create a new order using Saga orchestration.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $data = array_merge(
            $request->validated(),
            [
                'tenant_id' => $request->attributes->get('tenant_id'),
                'user_id' => $request->attributes->get('auth_user')['id'] ?? null,
            ]
        );

        $result = $this->orderService->createOrder($data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed. All changes have been rolled back.',
                'error' => $result['error'] ?? 'Saga compensation executed.',
                'saga_id' => $result['saga_id'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => $result['order'],
            'saga_id' => $result['saga_id'],
        ], 201);
    }

    /**
     * Get a specific order.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }

    /**
     * Cancel an order.
     */
    public function cancel(int $id): JsonResponse
    {
        $result = $this->orderService->cancelOrder($id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Handle saga event (internal endpoint for service-to-service communication).
     */
    public function sagaEvent(Request $request): JsonResponse
    {
        $request->validate([
            'saga_id' => ['required', 'string'],
            'event' => ['required', 'string'],
            'payload' => ['sometimes', 'array'],
        ]);

        $this->orderService->handleSagaEvent(
            $request->string('saga_id')->toString(),
            $request->string('event')->toString(),
            $request->input('payload', [])
        );

        return response()->json(['success' => true]);
    }
}
