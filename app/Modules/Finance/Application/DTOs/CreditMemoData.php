<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class CreditMemoData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $party_id,
        public readonly string $party_type,
        public readonly string $credit_memo_number,
        public readonly float $amount,
        public readonly string $issued_date,
        public readonly string $status = 'draft',
        public readonly ?int $return_order_id = null,
        public readonly ?string $return_order_type = null,
        public readonly ?int $applied_to_invoice_id = null,
        public readonly ?string $applied_to_invoice_type = null,
        public readonly ?string $notes = null,
        public readonly ?int $journal_entry_id = null,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            party_id: (int) $data['party_id'],
            party_type: (string) $data['party_type'],
            credit_memo_number: (string) $data['credit_memo_number'],
            amount: (float) $data['amount'],
            issued_date: (string) $data['issued_date'],
            status: (string) ($data['status'] ?? 'draft'),
            return_order_id: isset($data['return_order_id']) ? (int) $data['return_order_id'] : null,
            return_order_type: isset($data['return_order_type']) ? (string) $data['return_order_type'] : null,
            applied_to_invoice_id: isset($data['applied_to_invoice_id']) ? (int) $data['applied_to_invoice_id'] : null,
            applied_to_invoice_type: isset($data['applied_to_invoice_type']) ? (string) $data['applied_to_invoice_type'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            journal_entry_id: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
