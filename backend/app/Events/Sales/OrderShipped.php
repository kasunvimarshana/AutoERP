<?php

declare(strict_types=1);

namespace App\Events\Sales;

use App\Core\Events\BaseEvent;

/**
 * Order Shipped Event
 * 
 * Dispatched when an order is shipped to customer
 */
class OrderShipped extends BaseEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        public readonly int $customerId,
        public readonly string $customerName,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $carrier = null,
        public readonly ?\DateTimeInterface $shippedAt = null,
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
            'tracking_number' => $this->trackingNumber,
            'carrier' => $this->carrier,
            'shipped_at' => $this->shippedAt?->format('Y-m-d H:i:s'),
        ]);
    }
}
