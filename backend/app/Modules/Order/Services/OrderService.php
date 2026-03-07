<?php
namespace App\Modules\Order\Services;

use App\Helpers\PaginationHelper;
use App\Interfaces\MessageBrokerInterface;
use App\Models\Inventory;
use App\Models\Order;
use App\Modules\Order\Repositories\OrderRepository;
use App\Sagas\OrderSaga;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderSaga $orderSaga,
        private MessageBrokerInterface $messageBroker,
    ) {}

    public function listOrders(array $filters, int $tenantId): mixed
    {
        $filters['tenant_id'] = $tenantId;
        ['per_page' => $perPage, 'page' => $page] = PaginationHelper::fromRequest(request());

        $query = $this->orderRepository->all($filters, ['items', 'items.product', 'user']);
        return PaginationHelper::paginate($query, $perPage, $page);
    }

    public function getOrder(int $id): mixed
    {
        return $this->orderRepository->find($id, ['items', 'items.product', 'user', 'tenant']);
    }

    public function createOrder(array $data, int $tenantId, int $userId): mixed
    {
        $data['tenant_id'] = $tenantId;
        $data['user_id'] = $userId;

        return $this->orderSaga->execute($data);
    }

    public function updateOrderStatus(int $id, string $status): mixed
    {
        $order = $this->orderRepository->update($id, ['status' => $status]);

        $this->messageBroker->publish('order.status_changed', [
            'order_id' => $id,
            'status' => $status,
            'tenant_id' => $order->tenant_id,
        ]);

        return $order;
    }

    public function cancelOrder(int $id, string $reason = ''): mixed
    {
        $order = $this->orderRepository->find($id, ['items']);

        foreach ($order->items as $item) {
            if ($item->inventory_id) {
                $inventory = Inventory::find($item->inventory_id);
                if ($inventory) {
                    $inventory->decrement('reserved_quantity', min($item->quantity, $inventory->reserved_quantity));
                }
            }
        }

        $order->update(['status' => Order::STATUS_CANCELLED, 'notes' => $reason]);

        $this->messageBroker->publish('order.cancelled', [
            'order_id' => $id,
            'reason' => $reason,
            'tenant_id' => $order->tenant_id,
        ]);

        return $order->fresh();
    }
}
