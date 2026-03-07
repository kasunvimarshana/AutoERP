<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Events\OrderCancelled;
use App\Events\OrderCompleted;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Saga\OrderCreationSaga;
use App\Saga\Steps\ConfirmOrderStep;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InventoryServiceClient $inventoryClient,
        private readonly PaymentService $paymentService,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->orderRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Order
    {
        return $this->orderRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Order not found.');
    }

    public function create(OrderDTO $dto): Order
    {
        $order = DB::transaction(function () use ($dto) {
            $totalAmount = collect($dto->items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

            $order = $this->orderRepository->create([
                'tenant_id' => $dto->tenantId,
                'user_id' => $dto->userId,
                'status' => Order::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'shipping_address' => $dto->shippingAddress,
                'notes' => $dto->notes,
                'saga_state' => ['started_at' => now()->toIso8601String()],
            ]);

            foreach ($dto->items as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'warehouse_id' => $itemData['warehouse_id'],
                    'product_name' => $itemData['product_name'] ?? "Product #{$itemData['product_id']}",
                    'product_sku' => $itemData['product_sku'] ?? '',
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                ]);
            }

            return $order->fresh(['items']);
        });

        try {
            $saga = (new OrderCreationSaga)
                ->addStep(new CreateOrderStep)
                ->addStep(new ReserveInventoryStep($this->inventoryClient))
                ->addStep(new ProcessPaymentStep($this->paymentService))
                ->addStep(new ConfirmOrderStep($this->inventoryClient));

            $completedOrder = $saga->execute($order);
            event(new OrderCompleted($completedOrder));

            return $completedOrder;

        } catch (\RuntimeException $e) {
            $failedOrder = $this->orderRepository->findById($order->id);
            event(new OrderCancelled($failedOrder));
            throw ValidationException::withMessages(['order' => [$e->getMessage()]]);
        }
    }

    public function cancel(int $id): Order
    {
        return DB::transaction(function () use ($id) {
            $order = $this->findById($id);

            if (! $order->canBeCancelled()) {
                throw ValidationException::withMessages(['order' => ['Order cannot be cancelled in its current status.']]);
            }

            foreach ($order->items as $item) {
                try {
                    $this->inventoryClient->releaseInventory($item->product_id, $item->warehouse_id, $item->quantity, $order->tenant_id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Cancel order: Failed to release inventory', ['order_id' => $id, 'error' => $e->getMessage()]);
                }
            }

            $updated = $this->orderRepository->update($order, ['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]);
            event(new OrderCancelled($updated));

            return $updated;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $order = $this->findById($id);
            if ($order->isCompleted()) {
                throw ValidationException::withMessages(['order' => ['Completed orders cannot be deleted.']]);
            }
            $this->orderRepository->delete($order);
        });
    }
}
