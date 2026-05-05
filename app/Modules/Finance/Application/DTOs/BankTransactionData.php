<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class BankTransactionData
{
    public function __construct(
        public readonly ?int $tenant_id,
        public readonly int $bankAccountId,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $type,
        public readonly string $transactionDate,
        public readonly ?string $externalId = null,
        public readonly ?float $balance = null,
        public readonly string $status = 'imported',
        public readonly ?int $matchedJournalEntryId = null,
        public readonly ?int $categoryRuleId = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            bankAccountId: (int) $data['bank_account_id'],
            description: (string) $data['description'],
            amount: (float) $data['amount'],
            type: (string) $data['type'],
            transactionDate: (string) $data['transaction_date'],
            externalId: isset($data['external_id']) ? (string) $data['external_id'] : null,
            balance: isset($data['balance']) ? (float) $data['balance'] : null,
            status: (string) ($data['status'] ?? 'imported'),
            matchedJournalEntryId: isset($data['matched_journal_entry_id']) ? (int) $data['matched_journal_entry_id'] : null,
            categoryRuleId: isset($data['category_rule_id']) ? (int) $data['category_rule_id'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
