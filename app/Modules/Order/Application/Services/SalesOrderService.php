<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Order\Application\Contracts\SalesOrderServiceInterface;
use Modules\Order\Domain\Entities\OrderLine;
use Modules\Order\Domain\Entities\SalesOrder;
use Modules\Order\Domain\Events\SalesOrderCreated;
use Modules\Order\Domain\RepositoryInterfaces\OrderLineRepositoryInterface;
use Modules\Order\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;

class SalesOrderService implements SalesOrderServiceInterface
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function getSalesOrder(string $tenantId, string $id): SalesOrder
    {
        $order = $this->salesOrderRepository->findById($tenantId, $id);

        if ($order === null) {
            throw new NotFoundException('SalesOrder', $id);
        }

        return $order;
    }

    public function getAllSalesOrders(string $tenantId): array
    {
        return $this->salesOrderRepository->findAll($tenantId);
    }

    public function createSalesOrder(string $tenantId, array $data): SalesOrder
    {
        return DB::transaction(function () use ($tenantId, $data): SalesOrder {
            $now = now();
            $lines = $data['lines'] ?? [];

            $totalAmount = 0.0;
            foreach ($lines as $lineData) {
                $qty = (float) ($lineData['quantity'] ?? 0.0);
                $price = (float) ($lineData['unit_price'] ?? 0.0);
                $discount = (float) ($lineData['discount'] ?? 0.0);
                $taxRate = (float) ($lineData['tax_rate'] ?? 0.0);
                $totalAmount += round($qty * $price * (1 - $discount / 100.0) * (1 + $taxRate / 100.0), 2);
            }

            $order = new SalesOrder(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                customerId: $data['customer_id'],
                warehouseId: $data['warehouse_id'],
                reference: $data['reference'],
                status: 'draft',
                orderDate: new \DateTimeImmutable($data['order_date']),
                expectedDate: isset($data['expected_date'])
                    ? new \DateTimeImmutable($data['expected_date'])
                    : null,
                notes: $data['notes'] ?? null,
                totalAmount: $totalAmount,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->salesOrderRepository->save($order);

            foreach ($lines as $lineData) {
                $qty = (float) ($lineData['quantity'] ?? 0.0);
                $price = (float) ($lineData['unit_price'] ?? 0.0);
                $discount = (float) ($lineData['discount'] ?? 0.0);
                $taxRate = (float) ($lineData['tax_rate'] ?? 0.0);
                $lineTotal = round($qty * $price * (1 - $discount / 100.0) * (1 + $taxRate / 100.0), 2);

                $line = new OrderLine(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    orderType: 'sales',
                    orderId: $order->id,
                    productId: $lineData['product_id'],
                    variantId: $lineData['variant_id'] ?? null,
                    description: $lineData['description'] ?? null,
                    quantity: $qty,
                    unitPrice: $price,
                    discount: $discount,
                    taxRate: $taxRate,
                    lineTotal: $lineTotal,
                    createdAt: $now,
                    updatedAt: $now,
                );

                $this->orderLineRepository->save($line);
            }

            Event::dispatch(new SalesOrderCreated($order));

            return $order;
        });
    }

    public function confirmSalesOrder(string $tenantId, string $id): SalesOrder
    {
        return DB::transaction(function () use ($tenantId, $id): SalesOrder {
            $existing = $this->getSalesOrder($tenantId, $id);

            $updated = new SalesOrder(
                id: $existing->id,
                tenantId: $existing->tenantId,
                customerId: $existing->customerId,
                warehouseId: $existing->warehouseId,
                reference: $existing->reference,
                status: 'confirmed',
                orderDate: $existing->orderDate,
                expectedDate: $existing->expectedDate,
                notes: $existing->notes,
                totalAmount: $existing->totalAmount,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->salesOrderRepository->save($updated);

            return $updated;
        });
    }

    public function cancelSalesOrder(string $tenantId, string $id): SalesOrder
    {
        return DB::transaction(function () use ($tenantId, $id): SalesOrder {
            $existing = $this->getSalesOrder($tenantId, $id);

            $updated = new SalesOrder(
                id: $existing->id,
                tenantId: $existing->tenantId,
                customerId: $existing->customerId,
                warehouseId: $existing->warehouseId,
                reference: $existing->reference,
                status: 'cancelled',
                orderDate: $existing->orderDate,
                expectedDate: $existing->expectedDate,
                notes: $existing->notes,
                totalAmount: $existing->totalAmount,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->salesOrderRepository->save($updated);

            return $updated;
        });
    }

    public function deleteSalesOrder(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getSalesOrder($tenantId, $id);
            $this->salesOrderRepository->delete($tenantId, $id);
        });
    }

    public function updateSalesOrder(string $tenantId, string $id, array $data): SalesOrder
    {
        return DB::transaction(function () use ($tenantId, $id, $data): SalesOrder {
            $existing = $this->getSalesOrder($tenantId, $id);

            $updated = new SalesOrder(
                id: $existing->id,
                tenantId: $existing->tenantId,
                customerId: $data['customer_id'] ?? $existing->customerId,
                warehouseId: $data['warehouse_id'] ?? $existing->warehouseId,
                reference: $data['reference'] ?? $existing->reference,
                status: $data['status'] ?? $existing->status,
                orderDate: isset($data['order_date'])
                    ? new \DateTimeImmutable($data['order_date'])
                    : $existing->orderDate,
                expectedDate: isset($data['expected_date'])
                    ? new \DateTimeImmutable($data['expected_date'])
                    : $existing->expectedDate,
                notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->notes,
                totalAmount: $existing->totalAmount,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->salesOrderRepository->save($updated);

            return $updated;
        });
    }
}
