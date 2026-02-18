<?php

namespace App\Modules\Procurement\DTOs;

use App\Modules\Procurement\Enums\PurchaseOrderStatus;

/**
 * Purchase Order Data Transfer Object
 *
 * Encapsulates data for purchase order creation and updates.
 */
class PurchaseOrderDTO
{
    public function __construct(
        public readonly int $supplierId,
        public readonly int $warehouseId,
        public readonly string $poNumber,
        public readonly string $poDate,
        public readonly ?string $expectedDeliveryDate = null,
        public readonly ?PurchaseOrderStatus $status = null,
        public readonly ?float $subtotal = null,
        public readonly ?float $discountAmount = null,
        public readonly ?float $taxAmount = null,
        public readonly ?float $totalAmount = null,
        public readonly ?string $notes = null,
        public readonly ?array $items = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplierId: $data['supplier_id'],
            warehouseId: $data['warehouse_id'],
            poNumber: $data['po_number'],
            poDate: $data['po_date'],
            expectedDeliveryDate: $data['expected_delivery_date'] ?? null,
            status: isset($data['status']) ? PurchaseOrderStatus::from($data['status']) : null,
            subtotal: $data['subtotal'] ?? null,
            discountAmount: $data['discount_amount'] ?? null,
            taxAmount: $data['tax_amount'] ?? null,
            totalAmount: $data['total_amount'] ?? null,
            notes: $data['notes'] ?? null,
            items: $data['items'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'supplier_id' => $this->supplierId,
            'warehouse_id' => $this->warehouseId,
            'po_number' => $this->poNumber,
            'po_date' => $this->poDate,
            'expected_delivery_date' => $this->expectedDeliveryDate,
            'status' => $this->status?->value,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'notes' => $this->notes,
        ];
    }
}
