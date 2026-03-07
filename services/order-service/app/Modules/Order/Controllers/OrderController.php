<?php

namespace App\Modules\Order\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\DTOs\CreateOrderDTO;
use App\Modules\Order\Requests\StoreOrderRequest;
use App\Modules\Order\Requests\UpdateOrderRequest;
use App\Modules\Order\Requests\UpdateOrderStatusRequest;
use App\Modules\Order\Resources\OrderCollection;
use App\Modules\Order\Resources\OrderResource;
use App\Modules\Order\Services\Interfaces\OrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderServiceInterface $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'user_id', 'status', 'sort_by', 'sort_dir']);
        $perPage = min((int) $request->get('per_page', 15), 100);

        $orders = $this->orderService->listOrders($filters, $perPage);

        return (new OrderCollection($orders))->response();
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $dto = CreateOrderDTO::fromArray(array_merge(
            $request->validated(),
            ['user_id' => $request->validated('user_id') ?? $request->attributes->get('user_id')]
        ));

        $order = $this->orderService->createOrder($dto);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($id);
        return (new OrderResource($order))->response();
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->updateOrder($id, $request->validated());
        return (new OrderResource($order))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->orderService->deleteOrder($id);
        return response()->json(['message' => 'Order deleted successfully.'], 200);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->updateStatus($id, $request->validated('status'));
        return (new OrderResource($order))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $order = $this->orderService->cancelOrder($id);
        return (new OrderResource($order))->response();
    }
}
