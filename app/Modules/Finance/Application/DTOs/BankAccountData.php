<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankAccountData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $account_id,
        public readonly string $name,
        public readonly string $bank_name,
        public readonly string $account_number,
        public readonly int $currency_id,
        public readonly ?string $routing_number = null,
        public readonly float $current_balance = 0.0,
        public readonly ?string $feed_provider = null,
        public readonly bool $is_active = true,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            account_id: (int) $data['account_id'],
            name: (string) $data['name'],
            bank_name: (string) $data['bank_name'],
            account_number: (string) $data['account_number'],
            currency_id: (int) $data['currency_id'],
            routing_number: isset($data['routing_number']) ? (string) $data['routing_number'] : null,
            current_balance: (float) ($data['current_balance'] ?? 0.0),
            feed_provider: isset($data['feed_provider']) ? (string) $data['feed_provider'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
