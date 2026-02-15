<?php

declare(strict_types=1);

namespace App\Events\Manufacturing;

use App\Core\Events\BaseEvent;

/**
 * Work Order Started Event
 * 
 * Dispatched when a manufacturing work order is started
 */
class WorkOrderStarted extends BaseEvent
{
    public function __construct(
        public readonly int $workOrderId,
        public readonly string $workOrderNumber,
        public readonly int $productId,
        public readonly string $productName,
        public readonly float $quantity,
        public readonly ?\DateTimeInterface $startedAt = null,
        public readonly ?\DateTimeInterface $expectedCompletionDate = null,
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
            'quantity' => $this->quantity,
            'started_at' => $this->startedAt?->format('Y-m-d H:i:s'),
            'expected_completion_date' => $this->expectedCompletionDate?->format('Y-m-d'),
        ]);
    }
}
