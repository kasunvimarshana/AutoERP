<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends BaseController
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->orderService->paginate(
            perPage: (int) $request->get('per_page', 15),
            relations: ['user', 'items.product'],
            filters: $request->only(['status', 'payment_status', 'user_id']),
            sortBy: $request->get('sort_by', 'created_at'),
            sortDirection: $request->get('sort_direction', 'desc'),
            search: $request->get('search', '')
        );

        return $this->paginatedResponse(
            $paginator->through(fn ($o) => new OrderResource($o))
        );
    }

    public function store(OrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $order = $this->orderService->placeOrder($data);

        return $this->createdResponse(
            new OrderResource($order->load('items.product')),
            'Order placed successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->findById($id, ['*'], ['items.product', 'user']);

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        return $this->successResponse(new OrderResource($order));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->cancelOrder($id, $request->get('reason', ''));
        return $this->successResponse(new OrderResource($order), 'Order cancelled');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled,refunded'],
        ]);

        $order = $this->orderService->updateStatus($id, $request->status);
        return $this->successResponse(new OrderResource($order), 'Order status updated');
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = $this->orderService->getOrdersByUser($request->user()->id);
        return $this->successResponse(OrderResource::collection($orders));
    }
}
