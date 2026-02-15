<?php

declare(strict_types=1);

namespace App\Events\Manufacturing;

use App\Core\Events\BaseEvent;

/**
 * Work Order Completed Event
 * 
 * Dispatched when a manufacturing work order is completed
 */
class WorkOrderCompleted extends BaseEvent
{
    public function __construct(
        public readonly int $workOrderId,
        public readonly string $workOrderNumber,
        public readonly int $productId,
        public readonly string $productName,
        public readonly float $quantityProduced,
        public readonly float $quantityDefective,
        public readonly \DateTimeInterface $completedAt,
        int $tenantId,
        ?int $userId = null,
        ?array $metadata = null
    ) {
        parent::__construct($tenantId, $userId, $metadata);
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'work_order_id' => $this->workOrderId,
            'work_order_number' => $this->workOrderNumber,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'quantity_produced' => $this->quantityProduced,
            'quantity_defective' => $this->quantityDefective,
            'completed_at' => $this->completedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
