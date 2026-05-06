<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxRuleData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $taxGroupId,
        public readonly ?int $productCategoryId = null,
        public readonly ?string $partyType = null,
        public readonly ?string $region = null,
        public readonly int $priority = 0,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            taxGroupId: (int) $data['tax_group_id'],
            productCategoryId: isset($data['product_category_id']) ? (int) $data['product_category_id'] : null,
            partyType: isset($data['party_type']) ? (string) $data['party_type'] : null,
            region: isset($data['region']) ? (string) $data['region'] : null,
            priority: (int) ($data['priority'] ?? 0),
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
