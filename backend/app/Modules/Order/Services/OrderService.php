<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderUpdated;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderSagaService $orderSagaService,
    ) {}

    public function listOrders(
        int $perPage = 15,
        array $filters = [],
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        return $this->orderRepository->paginate($perPage, $filters, $search, $sortBy, $sortDir);
    }

    public function getOrder(int $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    public function createOrder(array $data): Order
    {
        $data['order_number'] = $data['order_number'] ?? 'ORD-' . strtoupper(Str::random(10));
        $order = $this->orderSagaService->execute($data);
        Event::dispatch(new OrderCreated($order));
        return $order;
    }

    public function updateOrder(int $id, array $data): Order
    {
        $order = $this->orderRepository->update($id, $data);
        Event::dispatch(new OrderUpdated($order));
        return $order;
    }

    public function cancelOrder(int $id): Order
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw new \RuntimeException("Order {$id} not found");
        }

        if (in_array($order->status, ['shipped', 'delivered', 'cancelled'])) {
            throw new \RuntimeException("Cannot cancel order in status: {$order->status}");
        }

        $order = $this->orderRepository->updateStatus($id, 'cancelled');
        Event::dispatch(new OrderCancelled($order));
        return $order;
    }

    public function deleteOrder(int $id): bool
    {
        return $this->orderRepository->delete($id);
    }
}
