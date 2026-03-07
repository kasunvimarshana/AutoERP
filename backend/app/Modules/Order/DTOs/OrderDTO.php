<?php

namespace App\Modules\Order\DTOs;

use App\Modules\Order\Models\Order;

readonly class OrderDTO
{
    public function __construct(
        public ?int $id,
        public ?int $tenantId,
        public string $orderNumber,
        public string $customerName,
        public string $customerEmail,
        public string $status,
        public float $subtotal,
        public float $tax,
        public float $total,
        public ?string $notes,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenantId: $data['tenant_id'] ?? null,
            orderNumber: $data['order_number'],
            customerName: $data['customer_name'],
            customerEmail: $data['customer_email'],
            status: $data['status'] ?? 'pending',
            subtotal: (float) ($data['subtotal'] ?? 0),
            tax: (float) ($data['tax'] ?? 0),
            total: (float) ($data['total'] ?? 0),
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public static function fromModel(Order $order): self
    {
        return new self(
            id: $order->id,
            tenantId: $order->tenant_id,
            orderNumber: $order->order_number,
            customerName: $order->customer_name,
            customerEmail: $order->customer_email,
            status: $order->status,
            subtotal: (float) $order->subtotal,
            tax: (float) $order->tax,
            total: (float) $order->total,
            notes: $order->notes,
            metadata: $order->metadata ?? [],
        );
    }
}
