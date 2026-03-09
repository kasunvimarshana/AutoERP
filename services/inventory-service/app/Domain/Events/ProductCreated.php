<?php

namespace App\Domain\Events;

class ProductCreated
{
    public string $eventType = 'inventory.product.created';

    public function __construct(
        public readonly string $productId,
        public readonly string $tenantId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $categoryId,
        public readonly float $unitPrice,
        public readonly string $currency,
        public readonly \DateTimeInterface $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function toArray(): array
    {
        return [
            'event_type'  => $this->eventType,
            'product_id'  => $this->productId,
            'tenant_id'   => $this->tenantId,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'category_id' => $this->categoryId,
            'unit_price'  => $this->unitPrice,
            'currency'    => $this->currency,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
