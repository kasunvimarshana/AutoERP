<?php

namespace App\Modules\POS\DTOs;

class SalesOrderDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $warehouseId,
        public readonly int $userId,
        public readonly string $orderDate,
        public readonly ?string $deliveryDate,
        public readonly array $items,
        public readonly ?string $notes = null,
        public readonly ?string $customerNotes = null,
        public readonly ?string $termsAndConditions = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
            warehouseId: $data['warehouse_id'],
            userId: $data['user_id'],
            orderDate: $data['order_date'],
            deliveryDate: $data['delivery_date'] ?? null,
            items: $data['items'] ?? [],
            notes: $data['notes'] ?? null,
            customerNotes: $data['customer_notes'] ?? null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'warehouse_id' => $this->warehouseId,
            'user_id' => $this->userId,
            'order_date' => $this->orderDate,
            'delivery_date' => $this->deliveryDate,
            'notes' => $this->notes,
            'customer_notes' => $this->customerNotes,
            'terms_and_conditions' => $this->termsAndConditions,
        ];
    }
}
