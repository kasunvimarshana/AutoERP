<?php
namespace App\Services;
use App\DTOs\PlaceOrderDTO;
use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService {
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly OrderItemRepositoryInterface $itemRepo,
        private readonly OrderSagaService $sagaService,
        private readonly ProductClientService $productClient,
    ) {}

    public function placeOrder(PlaceOrderDTO $dto): Order {
        $productIds = array_map(fn($i) => $i->productId, $dto->items);
        $products = $this->productClient->validateProducts($dto->tenantId, $productIds);
        $total = array_sum(array_map(fn($i) => $i->subtotal, $dto->items));
        $order = DB::transaction(function() use ($dto, $total) {
            $order = $this->orderRepo->create(['tenant_id' => $dto->tenantId, 'user_id' => $dto->userId, 'status' => Order::STATUS_PENDING, 'total_amount' => $total, 'currency' => $dto->currency, 'notes' => $dto->notes, 'shipping_address' => $dto->shippingAddress]);
            foreach ($dto->items as $item) {
                $this->itemRepo->create(['order_id' => $order->id, 'product_id' => $item->productId, 'product_name' => $item->productName, 'quantity' => $item->quantity, 'unit_price' => $item->unitPrice, 'subtotal' => $item->subtotal, 'metadata' => $item->metadata]);
            }
            return $order;
        });
        $this->sagaService->startSaga($order);
        event(new OrderPlaced($order));
        return $order;
    }

    public function getOrdersForTenant(string $tenantId, array $filters = []): LengthAwarePaginator {
        return $this->orderRepo->findByTenantId($tenantId, $filters);
    }

    public function getOrder(string $id, string $tenantId): Order {
        return $this->orderRepo->findWithItems($id, $tenantId);
    }

    public function updateOrder(string $id, string $tenantId, array $data): Order {
        $this->orderRepo->findById($id, $tenantId);
        return $this->orderRepo->update($id, $data);
    }

    public function cancelOrder(string $id, string $tenantId, ?string $reason = null): Order {
        $order = $this->orderRepo->findById($id, $tenantId);
        if (in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true)) {
            throw OrderException::cannotCancel($id, $order->status);
        }
        $sagaState = $this->sagaService->getSagaState($id);
        if ($sagaState && $sagaState->status === 'started') {
            $this->sagaService->compensate($order, $sagaState->current_step ?? 'unknown', $reason ?? 'Cancelled by user');
        }
        $order = $this->orderRepo->updateStatus($id, Order::STATUS_CANCELLED);
        event(new OrderCancelled($order, $reason));
        return $order;
    }

    public function updateStatus(string $id, string $tenantId, string $status): Order {
        $this->orderRepo->findById($id, $tenantId);
        $order = $this->orderRepo->updateStatus($id, $status);
        if ($status === Order::STATUS_COMPLETED) event(new OrderCompleted($order));
        return $order;
    }

    public function deleteOrder(string $id, string $tenantId): bool {
        $this->orderRepo->findById($id, $tenantId);
        return $this->orderRepo->delete($id);
    }
}
