<?php

declare(strict_types=1);

namespace Modules\Order\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Order\Application\Contracts\OrderLineServiceInterface;
use Modules\Order\Domain\Entities\OrderLine;
use Modules\Order\Domain\RepositoryInterfaces\OrderLineRepositoryInterface;

class OrderLineService implements OrderLineServiceInterface
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function getOrderLine(string $tenantId, string $id): OrderLine
    {
        $line = $this->orderLineRepository->findById($tenantId, $id);

        if ($line === null) {
            throw new NotFoundException('OrderLine', $id);
        }

        return $line;
    }

    public function getLinesForOrder(string $tenantId, string $orderType, string $orderId): array
    {
        return $this->orderLineRepository->findByOrder($tenantId, $orderType, $orderId);
    }

    public function addOrderLine(string $tenantId, array $data): OrderLine
    {
        return DB::transaction(function () use ($tenantId, $data): OrderLine {
            $now = now();
            $qty = (float) ($data['quantity'] ?? 0.0);
            $price = (float) ($data['unit_price'] ?? 0.0);
            $discount = (float) ($data['discount'] ?? 0.0);
            $taxRate = (float) ($data['tax_rate'] ?? 0.0);
            $lineTotal = round($qty * $price * (1 - $discount / 100.0) * (1 + $taxRate / 100.0), 2);

            $line = new OrderLine(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                orderType: $data['order_type'],
                orderId: $data['order_id'],
                productId: $data['product_id'],
                variantId: $data['variant_id'] ?? null,
                description: $data['description'] ?? null,
                quantity: $qty,
                unitPrice: $price,
                discount: $discount,
                taxRate: $taxRate,
                lineTotal: $lineTotal,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->orderLineRepository->save($line);

            return $line;
        });
    }

    public function updateOrderLine(string $tenantId, string $id, array $data): OrderLine
    {
        return DB::transaction(function () use ($tenantId, $id, $data): OrderLine {
            $existing = $this->getOrderLine($tenantId, $id);

            $qty = isset($data['quantity']) ? (float) $data['quantity'] : $existing->quantity;
            $price = isset($data['unit_price']) ? (float) $data['unit_price'] : $existing->unitPrice;
            $discount = isset($data['discount']) ? (float) $data['discount'] : $existing->discount;
            $taxRate = isset($data['tax_rate']) ? (float) $data['tax_rate'] : $existing->taxRate;
            $lineTotal = round($qty * $price * (1 - $discount / 100.0) * (1 + $taxRate / 100.0), 2);

            $updated = new OrderLine(
                id: $existing->id,
                tenantId: $existing->tenantId,
                orderType: $existing->orderType,
                orderId: $existing->orderId,
                productId: $data['product_id'] ?? $existing->productId,
                variantId: array_key_exists('variant_id', $data) ? $data['variant_id'] : $existing->variantId,
                description: array_key_exists('description', $data) ? $data['description'] : $existing->description,
                quantity: $qty,
                unitPrice: $price,
                discount: $discount,
                taxRate: $taxRate,
                lineTotal: $lineTotal,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->orderLineRepository->save($updated);

            return $updated;
        });
    }

    public function deleteOrderLine(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getOrderLine($tenantId, $id);
            $this->orderLineRepository->delete($tenantId, $id);
        });
    }
}
