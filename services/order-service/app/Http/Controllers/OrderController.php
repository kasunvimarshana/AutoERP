<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderSagaOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(private readonly OrderSagaOrchestrator $saga) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');
        $orders   = Order::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id'      => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.sku'      => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price'    => 'required|numeric|min:0',
            'currency'         => 'sometimes|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['tenant_id']    = $request->header('X-Tenant-ID');
        $data['total_amount'] = collect($data['items'])->sum(
            fn ($item) => $item['price'] * $item['quantity']
        );
        $data['currency'] = $data['currency'] ?? 'USD';

        $order = $this->saga->startOrderSaga($data);

        return response()->json($order, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');
        $order    = Order::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return response()->json($order);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID');
        $order    = Order::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if (! in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
            return response()->json(
                ['error' => 'Order cannot be cancelled in its current state.'],
                409
            );
        }

        $order = $this->saga->compensateOrderSaga($order, 'customer_cancellation');

        return response()->json($order);
    }

    /**
     * Internal endpoint called by other services (Saga event callbacks).
     */
    public function sagaCallback(Request $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $event = $request->input('event');
        $data  = $request->input('data', []);

        $order = $this->saga->handleSagaEvent($order, $event, $data);

        return response()->json($order);
    }
}
