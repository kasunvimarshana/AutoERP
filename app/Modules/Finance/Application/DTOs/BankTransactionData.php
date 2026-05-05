<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankTransactionData
{
    public function __construct(
        public readonly ?int $tenant_id,
        public readonly int $bank_account_id,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $type,
        public readonly string $transaction_date,
        public readonly ?string $external_id = null,
        public readonly ?float $balance = null,
        public readonly string $status = 'imported',
        public readonly ?int $matched_journal_entry_id = null,
        public readonly ?int $category_rule_id = null,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            bank_account_id: (int) $data['bank_account_id'],
            description: (string) $data['description'],
            amount: (float) $data['amount'],
            type: (string) $data['type'],
            transaction_date: (string) $data['transaction_date'],
            external_id: isset($data['external_id']) ? (string) $data['external_id'] : null,
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            status: (string) ($data['status'] ?? 'imported'),
            matched_journal_entry_id: isset($data['matched_journal_entry_id']) ? (int) $data['matched_journal_entry_id'] : null,
            category_rule_id: isset($data['category_rule_id']) ? (int) $data['category_rule_id'] : null,
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
