<?php

namespace App\Modules\Manufacturing\DTOs;

/**
 * Complete Work Order Data Transfer Object
 *
 * Encapsulates data for completing a work order.
 */
class CompleteWorkOrderDTO
{
    public function __construct(
        public readonly int $workOrderId,
        public readonly ?string $actualEnd = null,
        public readonly ?int $completedBy = null,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            workOrderId: $data['work_order_id'],
            actualEnd: $data['actual_end'] ?? null,
            completedBy: $data['completed_by'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'work_order_id' => $this->workOrderId,
            'actual_end' => $this->actualEnd,
            'completed_by' => $this->completedBy,
            'notes' => $this->notes,
        ];
    }
}
