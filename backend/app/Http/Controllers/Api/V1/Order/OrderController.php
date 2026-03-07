<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Order;

use App\Application\DTOs\OrderDTO;
use App\Application\Services\Order\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Thin controller for Order endpoints.
 */
final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * GET /api/v1/orders
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        return OrderResource::collection($this->orderService->list($request->query()));
    }

    /**
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Order::class);

        $tenantId = app('tenant.manager')->getCurrentTenantId();
        $result   = $this->orderService->placeOrder(OrderDTO::fromRequest($request), $tenantId);

        return response()->json([
            'message'      => 'Order placed successfully.',
            'order_id'     => $result['order_id'],
            'order_number' => $result['order_number'],
            'saga_id'      => $result['saga_id'],
        ], 201);
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(int $id): OrderResource
    {
        $order = $this->orderService->get($id);
        $this->authorize('view', $order);

        return OrderResource::make($order);
    }

    /**
     * PATCH /api/v1/orders/{id}/status
     */
    public function updateStatus(Request $request, int $id): OrderResource
    {
        $order = $this->orderService->get($id);
        $this->authorize('update', $order);

        $request->validate([
            'status'   => ['required', 'string', 'in:confirmed,processing,shipped,delivered,completed,cancelled,returned'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $updated = $this->orderService->updateStatus(
            $id,
            $request->string('status')->toString(),
            $request->input('metadata', [])
        );

        return OrderResource::make($updated);
    }

    /**
     * POST /api/v1/orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->get($id);
        $this->authorize('cancel', $order);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $cancelled = $this->orderService->cancel($id, $request->string('reason')->toString());

        return OrderResource::make($cancelled)->response();
    }

    /**
     * GET /api/v1/orders/summary
     */
    public function summary(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        return response()->json(['summary' => $this->orderService->statusSummary()]);
    }
}
