<?php

declare(strict_types=1);

namespace App\Events\Procurement;

use App\Core\Events\BaseEvent;

/**
 * Purchase Order Created Event
 * 
 * Dispatched when a new purchase order is created
 */
class PurchaseOrderCreated extends BaseEvent
{
    public function __construct(
        public readonly int $purchaseOrderId,
        public readonly string $poNumber,
        public readonly int $vendorId,
        public readonly string $vendorName,
        public readonly float $totalAmount,
        public readonly ?\DateTimeInterface $expectedDeliveryDate = null,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'purchase_order_id' => $this->purchaseOrderId,
            'po_number' => $this->poNumber,
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'total_amount' => $this->totalAmount,
            'expected_delivery_date' => $this->expectedDeliveryDate?->format('Y-m-d'),
        ]);
    }
}
