<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankCategoryRuleData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly array $conditions,
        public readonly int $account_id,
        public readonly ?int $bank_account_id = null,
        public readonly int $priority = 0,
        public readonly ?string $description_template = null,
        public readonly bool $is_active = true,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            conditions: (array) $data['conditions'],
            account_id: (int) $data['account_id'],
            bank_account_id: isset($data['bank_account_id']) ? (int) $data['bank_account_id'] : null,
            priority: (int) ($data['priority'] ?? 0),
            description_template: isset($data['description_template']) ? (string) $data['description_template'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
