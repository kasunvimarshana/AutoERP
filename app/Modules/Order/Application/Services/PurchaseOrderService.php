<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Order\Application\Contracts\PurchaseOrderServiceInterface;
use Modules\Order\Domain\Entities\OrderLine;
use Modules\Order\Domain\Entities\PurchaseOrder;
use Modules\Order\Domain\Events\PurchaseOrderCreated;
use Modules\Order\Domain\RepositoryInterfaces\OrderLineRepositoryInterface;
use Modules\Order\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;

class PurchaseOrderService implements PurchaseOrderServiceInterface
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function getPurchaseOrder(string $tenantId, string $id): PurchaseOrder
    {
        $order = $this->purchaseOrderRepository->findById($tenantId, $id);

        if ($order === null) {
            throw new NotFoundException('PurchaseOrder', $id);
        }

        return $order;
    }

    public function getAllPurchaseOrders(string $tenantId): array
    {
        return $this->purchaseOrderRepository->findAll($tenantId);
    }

    public function createPurchaseOrder(string $tenantId, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($tenantId, $data): PurchaseOrder {
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

            $order = new PurchaseOrder(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                supplierId: $data['supplier_id'],
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

            $this->purchaseOrderRepository->save($order);

            foreach ($lines as $lineData) {
                $qty = (float) ($lineData['quantity'] ?? 0.0);
                $price = (float) ($lineData['unit_price'] ?? 0.0);
                $discount = (float) ($lineData['discount'] ?? 0.0);
                $taxRate = (float) ($lineData['tax_rate'] ?? 0.0);
                $lineTotal = round($qty * $price * (1 - $discount / 100.0) * (1 + $taxRate / 100.0), 2);

                $line = new OrderLine(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    orderType: 'purchase',
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

            Event::dispatch(new PurchaseOrderCreated($order));

            return $order;
        });
    }

    public function confirmPurchaseOrder(string $tenantId, string $id): PurchaseOrder
    {
        return DB::transaction(function () use ($tenantId, $id): PurchaseOrder {
            $existing = $this->getPurchaseOrder($tenantId, $id);

            $updated = new PurchaseOrder(
                id: $existing->id,
                tenantId: $existing->tenantId,
                supplierId: $existing->supplierId,
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

            $this->purchaseOrderRepository->save($updated);

            return $updated;
        });
    }

    public function cancelPurchaseOrder(string $tenantId, string $id): PurchaseOrder
    {
        return DB::transaction(function () use ($tenantId, $id): PurchaseOrder {
            $existing = $this->getPurchaseOrder($tenantId, $id);

            $updated = new PurchaseOrder(
                id: $existing->id,
                tenantId: $existing->tenantId,
                supplierId: $existing->supplierId,
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

            $this->purchaseOrderRepository->save($updated);

            return $updated;
        });
    }

    public function deletePurchaseOrder(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getPurchaseOrder($tenantId, $id);
            $this->purchaseOrderRepository->delete($tenantId, $id);
        });
    }

    public function updatePurchaseOrder(string $tenantId, string $id, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($tenantId, $id, $data): PurchaseOrder {
            $existing = $this->getPurchaseOrder($tenantId, $id);

            $updated = new PurchaseOrder(
                id: $existing->id,
                tenantId: $existing->tenantId,
                supplierId: $data['supplier_id'] ?? $existing->supplierId,
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

            $this->purchaseOrderRepository->save($updated);

            return $updated;
        });
    }
}
