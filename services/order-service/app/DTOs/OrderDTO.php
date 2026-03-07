<?php

namespace App\DTOs;

readonly class OrderDTO
{
    public function __construct(
        public string  $tenantId,
        public string  $customerId,
        public string  $customerName,
        public string  $customerEmail,
        public array   $items,
        public array   $shippingAddress,
        public string  $paymentMethod,
        public ?array  $billingAddress = null,
        public ?string $notes = null,
        public ?array  $metadata = null,
        public string  $currency = 'USD',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:        $data['tenant_id'],
            customerId:      $data['customer_id'],
            customerName:    $data['customer_name'],
            customerEmail:   $data['customer_email'],
            items:           array_map(
                fn(array $item) => OrderItemDTO::fromArray($item),
                $data['items'] ?? []
            ),
            shippingAddress: $data['shipping_address'],
            paymentMethod:   $data['payment_method'],
            billingAddress:  $data['billing_address'] ?? null,
            notes:           $data['notes'] ?? null,
            metadata:        $data['metadata'] ?? null,
            currency:        $data['currency'] ?? 'USD',
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'       => $this->tenantId,
            'customer_id'     => $this->customerId,
            'customer_name'   => $this->customerName,
            'customer_email'  => $this->customerEmail,
            'items'           => array_map(
                fn(OrderItemDTO $item) => $item->toArray(),
                $this->items
            ),
            'shipping_address' => $this->shippingAddress,
            'payment_method'   => $this->paymentMethod,
            'billing_address'  => $this->billingAddress,
            'notes'            => $this->notes,
            'metadata'         => $this->metadata,
            'currency'         => $this->currency,
        ];
    }
}
