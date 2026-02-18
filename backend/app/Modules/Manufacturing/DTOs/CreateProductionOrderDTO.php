<?php

namespace App\Modules\Manufacturing\DTOs;

use App\Modules\Manufacturing\Enums\ProductionOrderPriority;
use App\Modules\Manufacturing\Enums\ProductionOrderStatus;

/**
 * Production Order Data Transfer Object
 *
 * Encapsulates data for production order creation and updates.
 */
class CreateProductionOrderDTO
{
    public function __construct(
        public readonly string $productionOrderNumber,
        public readonly int $productId,
        public readonly float $quantity,
        public readonly ?int $billOfMaterialId = null,
        public readonly ?int $warehouseId = null,
        public readonly ?string $scheduledStartDate = null,
        public readonly ?string $scheduledEndDate = null,
        public readonly ?ProductionOrderStatus $status = null,
        public readonly ?ProductionOrderPriority $priority = null,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productionOrderNumber: $data['production_order_number'],
            productId: $data['product_id'],
            quantity: $data['quantity'],
            billOfMaterialId: $data['bill_of_material_id'] ?? null,
            warehouseId: $data['warehouse_id'] ?? null,
            scheduledStartDate: $data['scheduled_start_date'] ?? null,
            scheduledEndDate: $data['scheduled_end_date'] ?? null,
            status: isset($data['status']) ? ProductionOrderStatus::from($data['status']) : null,
            priority: isset($data['priority']) ? ProductionOrderPriority::from($data['priority']) : null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'production_order_number' => $this->productionOrderNumber,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'bill_of_material_id' => $this->billOfMaterialId,
            'warehouse_id' => $this->warehouseId,
            'scheduled_start_date' => $this->scheduledStartDate,
            'scheduled_end_date' => $this->scheduledEndDate,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'notes' => $this->notes,
        ];
    }
}
