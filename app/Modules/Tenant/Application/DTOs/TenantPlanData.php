<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantPlanData
{
    /**
     * @param  array<string, mixed>|null  $features
     * @param  array<string, mixed>|null  $limits
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?array $features = null,
        public ?array $limits = null,
        public string|int|float $price = 0,
        public ?int $currencyId = null,
        public string $billingInterval = 'month',
        public bool $isActive = true,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            features: $data['features'] ?? null,
            limits: $data['limits'] ?? null,
            price: $data['price'] ?? 0,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            billingInterval: (string) ($data['billing_interval'] ?? 'month'),
            isActive: (bool) ($data['is_active'] ?? true),
            metadata: $data['metadata'] ?? null,
        );
    }
}
