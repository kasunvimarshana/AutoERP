<?php

namespace App\Modules\Order\Services;

use App\Base\BaseService;
use App\Modules\Order\Repositories\OrderRepository;
use App\Modules\Order\Repositories\OrderItemRepository;
use App\Modules\Order\DTOs\OrderDTO;
use App\Modules\Order\DTOs\OrderItemDTO;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderUpdated;
use App\Modules\Order\Events\OrderDeleted;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderCompleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class OrderService extends BaseService
{
    protected OrderItemRepository $orderItemRepository;

    public function __construct(OrderRepository $repository, OrderItemRepository $orderItemRepository)
    {
        parent::__construct($repository);
        $this->orderItemRepository = $orderItemRepository;
    }

    public function createOrder(OrderDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            $order = $this->repository->create($data);
            
            Event::dispatch(new OrderCreated($order));
            
            return $order;
        });
    }

    public function updateOrder(string $id, OrderDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $order = $this->repository->find($id);
            
            if (!$order) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $order->refresh();
                Event::dispatch(new OrderUpdated($order));
            }
            
            return $result;
        });
    }

    public function deleteOrder(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $order = $this->repository->find($id);
            
            if (!$order) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new OrderDeleted($order));
            }
            
            return $result;
        });
    }

    public function cancelOrder(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $order = $this->repository->find($id);
            
            if (!$order) {
                return false;
            }

            if (!$order->canBeCancelled()) {
                throw new \Exception('Order cannot be cancelled in its current status');
            }
            
            $result = $this->repository->update($id, ['status' => 'cancelled']);
            
            if ($result) {
                $order->refresh();
                Event::dispatch(new OrderCancelled($order));
            }
            
            return $result;
        });
    }

    public function completeOrder(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $order = $this->repository->find($id);
            
            if (!$order) {
                return false;
            }

            if (!$order->canBeCompleted()) {
                throw new \Exception('Order cannot be completed in its current status');
            }
            
            $result = $this->repository->update($id, ['status' => 'completed']);
            
            if ($result) {
                $order->refresh();
                Event::dispatch(new OrderCompleted($order));
            }
            
            return $result;
        });
    }

    public function addOrderItem(string $orderId, OrderItemDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($orderId, $dto) {
            $order = $this->repository->find($orderId);
            
            if (!$order) {
                throw new \Exception('Order not found');
            }
            
            $data = $dto->toArray();
            $data['order_id'] = $orderId;
            
            if (!isset($data['line_total']) || $data['line_total'] == 0) {
                $subtotal = $data['quantity'] * $data['unit_price'];
                $afterDiscount = $subtotal - ($data['discount_amount'] ?? 0);
                $taxAmount = $afterDiscount * ($data['tax_rate'] ?? 0);
                $data['line_total'] = $afterDiscount + $taxAmount;
            }
            
            $orderItem = $this->orderItemRepository->create($data);
            
            $this->recalculateOrderTotals($orderId);
            
            return $orderItem;
        });
    }

    public function updateOrderItem(string $itemId, OrderItemDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($itemId, $dto) {
            $orderItem = $this->orderItemRepository->find($itemId);
            
            if (!$orderItem) {
                return false;
            }
            
            $data = $dto->toArray();
            
            if (!isset($data['line_total']) || $data['line_total'] == 0) {
                $quantity = $data['quantity'] ?? $orderItem->quantity;
                $unitPrice = $data['unit_price'] ?? $orderItem->unit_price;
                $discountAmount = $data['discount_amount'] ?? $orderItem->discount_amount;
                $taxRate = $data['tax_rate'] ?? $orderItem->tax_rate;
                
                $subtotal = $quantity * $unitPrice;
                $afterDiscount = $subtotal - $discountAmount;
                $taxAmount = $afterDiscount * $taxRate;
                $data['line_total'] = $afterDiscount + $taxAmount;
            }
            
            $result = $this->orderItemRepository->update($itemId, $data);
            
            if ($result) {
                $this->recalculateOrderTotals($orderItem->order_id);
            }
            
            return $result;
        });
    }

    public function removeOrderItem(string $itemId): bool
    {
        return $this->executeInTransaction(function () use ($itemId) {
            $orderItem = $this->orderItemRepository->find($itemId);
            
            if (!$orderItem) {
                return false;
            }
            
            $orderId = $orderItem->order_id;
            
            $result = $this->orderItemRepository->delete($itemId);
            
            if ($result) {
                $this->recalculateOrderTotals($orderId);
            }
            
            return $result;
        });
    }

    public function calculateOrderTotal(string $orderId): array
    {
        $order = $this->repository->find($orderId, ['*'], ['orderItems']);
        
        if (!$order) {
            return [];
        }
        
        $totalAmount = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        
        foreach ($order->orderItems as $item) {
            $subtotal = $item->quantity * $item->unit_price;
            $totalAmount += $subtotal;
            $discountAmount += $item->discount_amount;
            
            $afterDiscount = $subtotal - $item->discount_amount;
            $taxAmount += $afterDiscount * $item->tax_rate;
        }
        
        $grandTotal = $totalAmount - $discountAmount + $taxAmount;
        
        return [
            'total_amount' => round($totalAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    protected function recalculateOrderTotals(string $orderId): void
    {
        $totals = $this->calculateOrderTotal($orderId);
        
        if (!empty($totals)) {
            $this->repository->update($orderId, $totals);
        }
    }

    public function findByOrderNumber(string $orderNumber): ?Model
    {
        return $this->repository->findByOrderNumber($orderNumber);
    }

    public function findByCustomer(string $customerId)
    {
        return $this->repository->findByCustomer($customerId);
    }

    public function findByTenant(string $tenantId)
    {
        return $this->repository->findByTenant($tenantId);
    }

    public function findByBranch(string $branchId)
    {
        return $this->repository->findByBranch($branchId);
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getByDateRange(string $from, string $to)
    {
        return $this->repository->getByDateRange($from, $to);
    }
}
