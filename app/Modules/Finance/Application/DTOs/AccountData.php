<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class AccountData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly string $normal_balance,
        public readonly ?int $parent_id = null,
        public readonly ?string $sub_type = null,
        public readonly bool $is_system = false,
        public readonly bool $is_bank_account = false,
        public readonly bool $is_credit_card = false,
        public readonly ?int $currency_id = null,
        public readonly ?string $description = null,
        public readonly bool $is_active = true,
        public readonly ?string $path = null,
        public readonly int $depth = 0,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            code: (string) $data['code'],
            name: (string) $data['name'],
            type: (string) $data['type'],
            normal_balance: (string) $data['normal_balance'],
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            sub_type: isset($data['sub_type']) ? (string) $data['sub_type'] : null,
            is_system: (bool) ($data['is_system'] ?? false),
            is_bank_account: (bool) ($data['is_bank_account'] ?? false),
            is_credit_card: (bool) ($data['is_credit_card'] ?? false),
            currency_id: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            path: isset($data['path']) ? (string) $data['path'] : null,
            depth: (int) ($data['depth'] ?? 0),
            rowVersion: (int) ($data['row_version'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
