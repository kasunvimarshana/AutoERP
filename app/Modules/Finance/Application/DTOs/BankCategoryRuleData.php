<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankCategoryRuleData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly array $conditions,
        public readonly int $accountId,
        public readonly ?int $bank_account_id = null,
        public readonly int $priority = 0,
        public readonly ?string $description_template = null,
        public readonly bool $isActive = true,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            conditions: (array) $data['conditions'],
            accountId: (int) $data['account_id'],
            bank_account_id: isset($data['bank_account_id']) ? (int) $data['bank_account_id'] : null,
            priority: (int) ($data['priority'] ?? 0),
            description_template: isset($data['description_template']) ? (string) $data['description_template'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
