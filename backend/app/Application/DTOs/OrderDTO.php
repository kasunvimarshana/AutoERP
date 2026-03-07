<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for Order create operations.
 */
final class OrderDTO
{
    /**
     * @param array<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function __construct(
        public readonly int|string $customerId,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly array $items,
        public readonly ?string $currency = 'USD',
        public readonly ?float $tax = 0.0,
        public readonly ?float $discount = 0.0,
        public readonly ?array $shippingAddress = null,
        public readonly ?array $billingAddress = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly int|string|null $tenantId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            customerId:      $request->input('customer_id'),
            customerName:    $request->string('customer_name')->toString(),
            customerEmail:   $request->string('customer_email')->lower()->toString(),
            items:           $request->input('items', []),
            currency:        $request->input('currency', 'USD'),
            tax:             (float) $request->input('tax', 0),
            discount:        (float) $request->input('discount', 0),
            shippingAddress: $request->input('shipping_address'),
            billingAddress:  $request->input('billing_address'),
            notes:           $request->input('notes'),
            metadata:        $request->input('metadata'),
            tenantId:        $request->input('tenant_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'customer_id'      => $this->customerId,
            'customer_name'    => $this->customerName,
            'customer_email'   => $this->customerEmail,
            'items'            => $this->items,
            'currency'         => $this->currency,
            'tax'              => $this->tax,
            'discount'         => $this->discount,
            'shipping_address' => $this->shippingAddress,
            'billing_address'  => $this->billingAddress,
            'notes'            => $this->notes,
            'metadata'         => $this->metadata,
            'tenant_id'        => $this->tenantId,
        ], fn ($v) => $v !== null);
    }
}
