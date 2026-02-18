<?php

namespace App\Modules\Procurement\DTOs;

/**
 * Purchase Order Receipt Data Transfer Object
 *
 * Encapsulates data for receiving goods from purchase orders.
 */
class PurchaseOrderReceiptDTO
{
    public function __construct(
        public readonly int $purchaseOrderId,
        public readonly array $receivedItems,
        public readonly ?int $receivedBy = null,
        public readonly ?string $receivedAt = null,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            purchaseOrderId: $data['purchase_order_id'],
            receivedItems: $data['received_items'],
            receivedBy: $data['received_by'] ?? null,
            receivedAt: $data['received_at'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'purchase_order_id' => $this->purchaseOrderId,
            'received_items' => $this->receivedItems,
            'received_by' => $this->receivedBy,
            'received_at' => $this->receivedAt,
            'notes' => $this->notes,
        ];
    }
}
