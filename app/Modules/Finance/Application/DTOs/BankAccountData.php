<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankAccountData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $accountId,
        public readonly string $name,
        public readonly string $bankName,
        public readonly string $accountNumber,
        public readonly int $currencyId,
        public readonly ?string $routingNumber = null,
        public readonly float $currentBalance = 0.0,
        public readonly ?string $feedProvider = null,
        public readonly bool $isActive = true,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            accountId: (int) $data['account_id'],
            name: (string) $data['name'],
            bankName: (string) $data['bank_name'],
            accountNumber: (string) $data['account_number'],
            currencyId: (int) $data['currency_id'],
            routingNumber: isset($data['routing_number']) ? (string) $data['routing_number'] : null,
            currentBalance: (float) ($data['current_balance'] ?? 0.0),
            feedProvider: isset($data['feed_provider']) ? (string) $data['feed_provider'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
