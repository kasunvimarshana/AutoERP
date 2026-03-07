<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\DTOs\CreateOrderDTO;
use App\Modules\Order\DTOs\OrderItemDTO;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Events\OrderUpdated;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Repositories\Interfaces\OrderRepositoryInterface;
use App\Modules\Order\Services\Interfaces\OrderServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {}

    public function listOrders(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($filters, $perPage);
    }

    public function getOrder(int $id): Order
    {
        $order = $this->orderRepository->findById($id);
        if (!$order) {
            throw new ModelNotFoundException("Order not found with ID: {$id}");
        }
        return $order;
    }

    public function createOrder(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            $orderNumber = $this->generateOrderNumber();

            $itemDTOs = array_map(
                fn (array $item) => OrderItemDTO::fromArray($item),
                $dto->items
            );

            $totalAmount = array_reduce(
                $itemDTOs,
                fn (float $carry, OrderItemDTO $item) => $carry + $item->getTotalPrice(),
                0.0
            );

            $order = $this->orderRepository->create([
                'order_number' => $orderNumber,
                'user_id' => $dto->userId,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'shipping_address' => $dto->shippingAddress,
                'notes' => $dto->notes,
                'metadata' => $dto->metadata,
            ]);

            foreach ($itemDTOs as $itemDTO) {
                $order->items()->create($itemDTO->toArray());
            }

            $order->load('items');

            Event::dispatch(new OrderCreated($order));

            return $order;
        });
    }

    public function updateOrder(int $id, array $data): Order
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->getOrder($id);

            $allowedFields = ['notes', 'metadata', 'shipping_address'];
            $filteredData = array_intersect_key($data, array_flip($allowedFields));

            $updated = $this->orderRepository->update($id, $filteredData);

            Event::dispatch(new OrderUpdated($updated));

            return $updated;
        });
    }

    public function deleteOrder(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->getOrder($id);
            $this->orderRepository->delete($id);
        });
    }

    public function updateStatus(int $id, string $status): Order
    {
        return DB::transaction(function () use ($id, $status) {
            $order = $this->getOrder($id);

            if (!in_array($status, Order::STATUSES)) {
                throw new \InvalidArgumentException("Invalid status: {$status}");
            }

            $previousStatus = $order->status;
            $updated = $this->orderRepository->updateStatus($id, $status);

            Event::dispatch(new OrderStatusChanged($updated, $previousStatus, $status));

            return $updated;
        });
    }

    public function cancelOrder(int $id): Order
    {
        return DB::transaction(function () use ($id) {
            $order = $this->getOrder($id);

            if (!$order->isCancellable()) {
                throw new \InvalidArgumentException(
                    "Order cannot be cancelled. Current status: {$order->status}"
                );
            }

            $updated = $this->orderRepository->updateStatus($id, Order::STATUS_CANCELLED);

            // Saga compensating transaction: notify inventory to release reserved stock
            Event::dispatch(new OrderCancelled($updated));

            return $updated;
        });
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
    }
}
