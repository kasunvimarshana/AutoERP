<?php
namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = app('tenant');
        $filters = $request->only(['status', 'user_id', 'order_number']);
        $orders = $this->orderService->listOrders($filters, $tenant->id);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->orderService->getOrder($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string',
        ]);

        $tenant = app('tenant');
        $user = $request->user();

        $result = $this->orderService->createOrder($validated, $tenant->id, $user->id);

        return response()->json(['success' => true, 'data' => $result, 'message' => 'Order created successfully'], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order = $this->orderService->updateOrderStatus($id, $validated['status']);

        return response()->json(['success' => true, 'data' => $order, 'message' => 'Order status updated']);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $order = $this->orderService->cancelOrder($id, $validated['reason'] ?? '');

        return response()->json(['success' => true, 'data' => $order, 'message' => 'Order cancelled']);
    }
}
