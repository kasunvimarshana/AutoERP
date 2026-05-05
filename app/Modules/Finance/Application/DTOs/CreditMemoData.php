<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class CreditMemoData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $partyId,
        public readonly string $partyType,
        public readonly string $creditMemoNumber,
        public readonly float $amount,
        public readonly string $issuedDate,
        public readonly string $status = 'draft',
        public readonly ?int $returnOrderId = null,
        public readonly ?string $returnOrderType = null,
        public readonly ?int $appliedToInvoiceId = null,
        public readonly ?string $appliedToInvoiceType = null,
        public readonly ?string $notes = null,
        public readonly ?int $journalEntryId = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            partyId: (int) $data['party_id'],
            partyType: (string) $data['party_type'],
            creditMemoNumber: (string) $data['credit_memo_number'],
            amount: (float) $data['amount'],
            issuedDate: (string) $data['issued_date'],
            status: (string) ($data['status'] ?? 'draft'),
            returnOrderId: isset($data['return_order_id']) ? (int) $data['return_order_id'] : null,
            returnOrderType: isset($data['return_order_type']) ? (string) $data['return_order_type'] : null,
            appliedToInvoiceId: isset($data['applied_to_invoice_id']) ? (int) $data['applied_to_invoice_id'] : null,
            appliedToInvoiceType: isset($data['applied_to_invoice_type']) ? (string) $data['applied_to_invoice_type'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
