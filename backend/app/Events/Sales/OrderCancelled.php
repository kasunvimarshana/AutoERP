<?php

declare(strict_types=1);

namespace App\Events\Sales;

use App\Core\Events\BaseEvent;

/**
 * Order Cancelled Event
 * 
 * Dispatched when an order is cancelled
 */
class OrderCancelled extends BaseEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly string $reason,
        public readonly float $totalAmount,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'reason' => $this->reason,
            'total_amount' => $this->totalAmount,
        ]);
    }
}
