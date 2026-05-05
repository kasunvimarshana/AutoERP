<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxRateData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $taxGroupId,
        public readonly string $name,
        public readonly string $rate,
        public readonly string $type = 'percentage',
        public readonly ?int $accountId = null,
        public readonly bool $isCompound = false,
        public readonly bool $isActive = true,
        public readonly ?string $validFrom = null,
        public readonly ?string $validTo = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            taxGroupId: (int) $data['tax_group_id'],
            name: (string) $data['name'],
            rate: (string) $data['rate'],
            type: (string) ($data['type'] ?? 'percentage'),
            accountId: isset($data['account_id']) ? (int) $data['account_id'] : null,
            isCompound: (bool) ($data['is_compound'] ?? false),
            isActive: (bool) ($data['is_active'] ?? true),
            validFrom: isset($data['valid_from']) ? (string) $data['valid_from'] : null,
            validTo: isset($data['valid_to']) ? (string) $data['valid_to'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
