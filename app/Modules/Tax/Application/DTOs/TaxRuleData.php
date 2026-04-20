<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxRuleData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $tax_group_id,
        public readonly ?int $product_category_id = null,
        public readonly ?string $party_type = null,
        public readonly ?string $region = null,
        public readonly int $priority = 0,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            tax_group_id: (int) $data['tax_group_id'],
            product_category_id: isset($data['product_category_id']) ? (int) $data['product_category_id'] : null,
            party_type: isset($data['party_type']) ? (string) $data['party_type'] : null,
            region: isset($data['region']) ? (string) $data['region'] : null,
            priority: (int) ($data['priority'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
