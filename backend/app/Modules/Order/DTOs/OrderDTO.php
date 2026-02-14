<?php

namespace App\Modules\Order\DTOs;

class OrderDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $branch_id = null,
        public ?string $customer_id = null,
        public ?string $order_number = null,
        public ?string $order_date = null,
        public ?string $status = 'pending',
        public ?float $total_amount = 0.00,
        public ?float $tax_amount = 0.00,
        public ?float $discount_amount = 0.00,
        public ?float $grand_total = 0.00,
        public ?string $notes = null,
        public ?array $settings = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            order_number: $data['order_number'] ?? null,
            order_date: $data['order_date'] ?? null,
            status: $data['status'] ?? 'pending',
            total_amount: isset($data['total_amount']) ? (float)$data['total_amount'] : 0.00,
            tax_amount: isset($data['tax_amount']) ? (float)$data['tax_amount'] : 0.00,
            discount_amount: isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.00,
            grand_total: isset($data['grand_total']) ? (float)$data['grand_total'] : 0.00,
            notes: $data['notes'] ?? null,
            settings: $data['settings'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'grand_total' => $this->grand_total,
            'notes' => $this->notes,
            'settings' => $this->settings,
        ], fn($value) => !is_null($value));
    }
}
