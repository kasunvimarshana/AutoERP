<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Models\Order;
use App\Saga\SagaOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private readonly SagaOrchestrator $orchestrator
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $orders = Order::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data'  => $orders->items(),
            'meta'  => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
            'links' => [
                'first' => $orders->url(1),
                'last'  => $orders->url($orders->lastPage()),
                'prev'  => $orders->previousPageUrl(),
                'next'  => $orders->nextPageUrl(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $order = Order::with('sagaStates')->find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $sagaState = $order->sagaStates()->latest()->first();

        return response()->json([
            'data' => array_merge($order->toArray(), [
                'saga' => $sagaState ? [
                    'id'           => $sagaState->saga_id,
                    'current_step' => $sagaState->current_step,
                    'status'       => $sagaState->status,
                    'error'        => $sagaState->error_message,
                ] : null,
            ]),
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $totalAmount = collect($validated['items'])
            ->sum(fn ($item) => $item['price'] * $item['quantity']);

        $order = Order::create([
            'customer_id'    => $validated['customer_id'],
            'customer_email' => $validated['customer_email'],
            'items'          => $validated['items'],
            'total_amount'   => $totalAmount,
            'status'         => Order::STATUS_PENDING,
        ]);

        Log::info('Order created', ['order_id' => $order->id, 'total' => $totalAmount]);

        try {
            $sagaId = $this->orchestrator->startSaga($order);

            $order->update([
                'status'     => Order::STATUS_PROCESSING,
                'saga_id'    => $sagaId,
                'saga_state' => 'STARTED',
            ]);

            Log::info('Saga started', ['saga_id' => $sagaId, 'order_id' => $order->id]);

            return response()->json([
                'message' => 'Order created and saga started',
                'data'    => [
                    'order_id' => $order->id,
                    'saga_id'  => $sagaId,
                    'status'   => $order->status,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Failed to start saga', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            $order->update(['status' => Order::STATUS_FAILED]);

            return response()->json([
                'message' => 'Order created but saga failed to start. Please retry.',
                'data'    => ['order_id' => $order->id, 'status' => Order::STATUS_FAILED],
            ], 500);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        $order = Order::find($id);

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $cancellableStatuses = [Order::STATUS_PENDING, Order::STATUS_PROCESSING];
        if (! in_array($order->status, $cancellableStatuses, true)) {
            return response()->json([
                'message' => 'Order cannot be cancelled in its current status',
                'status'  => $order->status,
            ], 422);
        }

        if ($order->saga_id) {
            try {
                $this->orchestrator->compensate($order->saga_id);
                Log::info('Compensation triggered for cancellation', ['order_id' => $id]);
            } catch (\Throwable $e) {
                Log::error('Failed to trigger compensation', [
                    'order_id' => $id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return response()->json([
            'message' => 'Order cancellation initiated',
            'data'    => ['order_id' => $order->id, 'status' => $order->status],
        ]);
    }
}
