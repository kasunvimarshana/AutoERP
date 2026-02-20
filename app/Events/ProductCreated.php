<?php

namespace App\Events;

use App\Contracts\Events\DomainEventInterface;
use App\Models\Product;
use DateTimeImmutable;

final class ProductCreated implements DomainEventInterface
{
    private DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly Product $product
    ) {
        $this->occurredAt = new DateTimeImmutable;
    }

    public function getAggregateId(): string
    {
        return $this->product->id;
    }

    public function getAggregateType(): string
    {
        return Product::class;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'sku' => $this->product->sku,
            'tenant_id' => $this->product->tenant_id,
            'type' => $this->product->type?->value,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
