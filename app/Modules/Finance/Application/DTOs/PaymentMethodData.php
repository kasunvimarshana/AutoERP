<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class PaymentMethodData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $type = 'bank_transfer',
        public readonly ?int $account_id = null,
        public readonly bool $is_active = true,
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
            name: (string) $data['name'],
            type: (string) ($data['type'] ?? 'bank_transfer'),
            account_id: isset($data['account_id']) ? (int) $data['account_id'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            rowVersion: (int) ($data['row_version'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
