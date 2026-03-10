<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ProductServiceClient;
use App\Services\SagaOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(
        private readonly SagaOrchestrator $saga,
        private readonly ProductServiceClient $productClient
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items');

        // Direct filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Cross-service product filters
        $productFilters = array_filter([
            'name'     => $request->input('product_name'),
            'code'     => $request->input('product_code'),
            'category' => $request->input('product_category'),
            'search'   => $request->input('product_search'),
        ]);

        if (!empty($productFilters)) {
            $products = $this->productClient->getProductsByFilters($productFilters);
            if (!empty($products)) {
                $productIds = array_column($products, 'id');
                $orderIds = OrderItem::whereIn('product_id', $productIds)->pluck('order_id')->unique()->toArray();
                $query->whereIn('id', $orderIds);
            } else {
                // No products matched → no orders
                $query->whereRaw('1 = 0');
            }
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data'    => [
                'orders'     => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                    'last_page'    => $orders->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity'   => 'required|integer|min:1',
            'notes'          => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $authUser = $request->attributes->get('auth_user');
        $token = $request->bearerToken();

        $orderData = [
            'user_id'    => $authUser['id'],
            'user_email' => $authUser['email'],
            'items'      => $request->input('items'),
            'notes'      => $request->input('notes'),
        ];

        $result = $this->saga->createOrderSaga($orderData, $token);

        $statusCode = $result['success'] ? 201 : 422;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ], $statusCode);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data'    => ['order' => $order],
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,confirmed,processing,shipped,delivered,cancelled,failed',
            'notes'  => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $order->update($request->only(['status', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data'    => ['order' => $order->fresh()->load('items')],
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        return $this->cancel($request, $id);
    }

    public function cancel(Request $request, $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if (in_array($order->status, ['cancelled', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be cancelled (current status: {$order->status})",
            ], 422);
        }

        $token = $request->bearerToken();
        $result = $this->saga->cancelOrderSaga($order, $token);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ]);
    }
}
