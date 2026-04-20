<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

class PriceListData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $type,
        public readonly int $currency_id,
        public readonly bool $is_default = false,
        public readonly ?string $valid_from = null,
        public readonly ?string $valid_to = null,
        public readonly bool $is_active = true,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            type: (string) ($data['type'] ?? 'sales'),
            currency_id: (int) $data['currency_id'],
            is_default: (bool) ($data['is_default'] ?? false),
            valid_from: isset($data['valid_from']) ? (string) $data['valid_from'] : null,
            valid_to: isset($data['valid_to']) ? (string) $data['valid_to'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
