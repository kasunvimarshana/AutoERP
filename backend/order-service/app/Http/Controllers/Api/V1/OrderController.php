<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->list(
            $request->only(['status', 'user_id', 'date_from', 'date_to', 'sort_by', 'sort_dir']),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $dto = OrderDTO::fromArray(array_merge($request->validated(), [
            'tenant_id' => app('current_tenant_id'),
            'user_id' => $request->user()->id,
        ]));
        $order = $this->orderService->create($dto);

        return response()->json(['message' => 'Order created successfully.', 'data' => new OrderResource($order)], 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->findById($id);
        $this->authorize('view', $order);

        return response()->json(['data' => new OrderResource($order)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate(['notes' => 'sometimes|nullable|string', 'shipping_address' => 'sometimes|array']);
        $order = $this->orderService->findById($id);
        $this->authorize('update', $order);
        if (! $order->isPending()) {
            return response()->json(['message' => 'Only pending orders can be updated.'], 422);
        }
        $order->update($request->only(['notes', 'shipping_address']));

        return response()->json(['message' => 'Order updated.', 'data' => new OrderResource($order->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $order = $this->orderService->findById($id);
        $this->authorize('delete', $order);
        $this->orderService->delete($id);

        return response()->json(['message' => 'Order deleted.'], 204);
    }

    public function cancel(int $id): JsonResponse
    {
        $order = $this->orderService->findById($id);
        $this->authorize('update', $order);
        $cancelled = $this->orderService->cancel($id);

        return response()->json(['message' => 'Order cancelled.', 'data' => new OrderResource($cancelled)]);
    }

    public function sagaLog(int $id): JsonResponse
    {
        $order = $this->orderService->findById($id);
        $this->authorize('view', $order);

        return response()->json([
            'data' => $order->sagaLogs->map(fn ($log) => [
                'step_name' => $log->step_name,
                'status' => $log->status,
                'payload' => $log->payload,
                'error_message' => $log->error_message,
                'executed_at' => $log->executed_at?->toIso8601String(),
                'compensated_at' => $log->compensated_at?->toIso8601String(),
            ]),
        ]);
    }
}
