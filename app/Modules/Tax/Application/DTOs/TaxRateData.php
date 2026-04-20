<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxRateData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $tax_group_id,
        public readonly string $name,
        public readonly string $rate,
        public readonly string $type = 'percentage',
        public readonly ?int $account_id = null,
        public readonly bool $is_compound = false,
        public readonly bool $is_active = true,
        public readonly ?string $valid_from = null,
        public readonly ?string $valid_to = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            tax_group_id: (int) $data['tax_group_id'],
            name: (string) $data['name'],
            rate: (string) $data['rate'],
            type: (string) ($data['type'] ?? 'percentage'),
            account_id: isset($data['account_id']) ? (int) $data['account_id'] : null,
            is_compound: (bool) ($data['is_compound'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            valid_from: isset($data['valid_from']) ? (string) $data['valid_from'] : null,
            valid_to: isset($data['valid_to']) ? (string) $data['valid_to'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
