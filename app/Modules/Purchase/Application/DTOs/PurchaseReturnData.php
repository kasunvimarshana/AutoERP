<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

class PurchaseReturnData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $supplierId,
        public readonly string $returnNumber,
        public readonly string $returnDate,
        public readonly int $currencyId,
        public readonly string $status = 'draft',
        public readonly string $exchangeRate = '1',
        public readonly ?int $originalGrnId = null,
        public readonly ?int $originalInvoiceId = null,
        public readonly ?string $returnReason = null,
        public readonly string $subtotal = '0',
        public readonly string $taxTotal = '0',
        public readonly string $grandTotal = '0',
        public readonly ?string $debitNoteNumber = null,
        public readonly ?int $journalEntryId = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            supplierId: (int) $data['supplier_id'],
            returnNumber: (string) $data['return_number'],
            returnDate: (string) $data['return_date'],
            currencyId: (int) $data['currency_id'],
            status: isset($data['status']) ? (string) $data['status'] : 'draft',
            exchangeRate: isset($data['exchange_rate']) ? (string) $data['exchange_rate'] : '1',
            originalGrnId: isset($data['original_grn_id']) ? (int) $data['original_grn_id'] : null,
            originalInvoiceId: isset($data['original_invoice_id']) ? (int) $data['original_invoice_id'] : null,
            returnReason: isset($data['return_reason']) ? (string) $data['return_reason'] : null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : '0',
            taxTotal: isset($data['tax_total']) ? (string) $data['tax_total'] : '0',
            grandTotal: isset($data['grand_total']) ? (string) $data['grand_total'] : '0',
            debitNoteNumber: isset($data['debit_note_number']) ? (string) $data['debit_note_number'] : null,
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) ? (array) $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'supplier_id' => $this->supplierId,
            'return_number' => $this->returnNumber,
            'return_date' => $this->returnDate,
            'currency_id' => $this->currencyId,
            'status' => $this->status,
            'exchange_rate' => $this->exchangeRate,
            'original_grn_id' => $this->originalGrnId,
            'original_invoice_id' => $this->originalInvoiceId,
            'return_reason' => $this->returnReason,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->taxTotal,
            'grand_total' => $this->grandTotal,
            'debit_note_number' => $this->debitNoteNumber,
            'journal_entry_id' => $this->journalEntryId,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
